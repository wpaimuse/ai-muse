<?php

namespace AIMuse\Helpers;

use AIMuse\Exceptions\ControllerException;
use AIMuse\Models\Dataset;
use WP_Post;
use AIMuseVendor\Illuminate\Support\Facades\Log;
use AIMuseVendor\League\HTMLToMarkdown\HtmlConverter;
use AIMuseVendor\Illuminate\Support\Arr;

class PromptHelper
{
  private static HtmlConverter $converter;

  public static function replaceAllVariables(string $prompt)
  {
    $prompt = apply_filters('aimuse_pre_replace_prompt_variables', $prompt);

    $prompt = static::replaceSiteVariables($prompt);
    $postId = PostHelper::$id;

    if ($postId) {
      $post = get_post($postId);

      if ($post) {
        $prompt = static::replacePostVariables($post, $prompt);
        $prompt = static::replaceTaxonomyVariables($prompt, $postId);
        $prompt = static::replaceCustomFieldVariables($prompt, $postId);

        if (function_exists('wc_get_product') && $post->post_type === 'product') {
          $product = call_user_func('wc_get_product', $post->ID);
          $prompt = static::replaceProductVariables($product, $prompt);
        }
      }
    }

    $prompt = static::replaceCustomPostVariables($prompt);
    $prompt = static::replaceDatasetsVariables($prompt);
    $prompt = static::replaceShortcodeVariables($prompt);

    if (PostHelper::$isOverride) {
      $prompt = static::replaceTitleVariable($prompt);
      $prompt = static::replaceContentVariable($prompt);
    }

    $prompt = apply_filters('aimuse_after_replace_prompt_variables', $prompt);

    return $prompt;
  }

  public static function replaceTitleVariable(string $prompt)
  {
    $regex = '/{{post_title}}/m';

    return preg_replace($regex, PostHelper::getTitle(), $prompt);
  }

  public static function replaceContentVariable(string $prompt)
  {
    $regex = '/{{post_content}}/m';

    $content = PostHelper::getContent();
    $content = self::convertHtmlToMarkdown($content);

    return preg_replace($regex, $content, $prompt);
  }

  public static function replaceShortcodeVariables(string $prompt)
  {
    $regex = '/{{shortcode:(?<code>.*)}}/m';
    preg_match_all($regex, $prompt, $matches, PREG_SET_ORDER, 0);

    if (empty($matches)) return $prompt;

    foreach ($matches as $match) {
      $shortcode = $match['code'];
      $content = do_shortcode($shortcode);
      $content = self::convertHtmlToMarkdown($content);
      $shortcode = str_replace(['[', ']'], ['\[', '\]'], $shortcode);
      $prompt = preg_replace('/{{shortcode:' . $shortcode . '}}/m', $content, $prompt);
    }

    return $prompt;
  }

  public static function replaceDatasetsVariables(string $prompt)
  {
    $regex = '/{{dataset:(?<id>\d+)}}/m';
    preg_match_all($regex, $prompt, $matches, PREG_SET_ORDER, 0);

    $ids = Arr::pluck($matches, 'id');

    /**
     * @var Dataset[] $datasets
     */
    $datasets = Dataset::query()->whereIn('id', $ids)->get();

    foreach ($datasets as $dataset) {
      $prompt = static::replaceDatasetVariable($prompt, $dataset);
    }

    //remove any unused dataset placeholders
    $prompt = preg_replace($regex, '', $prompt);

    return $prompt;
  }

  public static function replaceDatasetVariable(string $prompt, Dataset $dataset)
  {
    $datasetPrompts = [];

    $dataset->conversations()->chunk(1000, function ($conversations) use (&$datasetPrompts) {
      if (MemoryHelper::getUsagePercentage() > 90) {
        throw ControllerException::make('Datasets are too large to process. Please use fine-tuned models instead.', 400);
      }

      foreach ($conversations as $conversation) {
        $datasetPrompts[] = '"' . "{$conversation->prompt} ➜ {$conversation->response}" . '"';
      }
    });

    $datasetPrompt = implode("\n", $datasetPrompts);

    return preg_replace(
      "/{{dataset:$dataset->id}}/m",
      $datasetPrompt,
      $prompt
    );
  }

  public static function replaceCustomPostVariables(string $prompt)
  {
    $postVariableRegex = [
      '/{{post_title:(?<id>\d+)}}/m',
      '/{{post_content:(?<id>\d+)}}/m',
      '/{{post_excerpt:(?<id>\d+)}}/m',
      '/{{post_tags:(?<id>\d+)}}/m',
      '/{{post_categories:(?<id>\d+)}}/m',
      '/{{post_type:(?<id>\d+)}}/m',
      '/{{taxonomy:(?<name>.+):(?<id>\d+)}}/m',
      '/{{custom_field:(?<name>.+):(?<id>\d+)}}/m',
    ];

    $ids = [];
    foreach ($postVariableRegex as $regex) {
      preg_match_all($regex, $prompt, $matches, PREG_SET_ORDER, 0);

      $ids = array_merge($ids, Arr::pluck($matches, 'id'));
    }

    $ids = array_unique($ids);

    if (count($ids) === 0) return $prompt;

    Log::debug('Found custom post variables', ['ids' => $ids]);

    foreach ($ids as $id) {
      $post = get_post($id);
      if (!$post) continue;

      // It assigns all post variables given with ID to normal post variables.
      // So we can use post variables without additional coding.
      $prompt = preg_replace("/{{(.*):$id}}/m", '{{$1}}', $prompt);

      $prompt = static::replacePostVariables($post, $prompt);
      $prompt = static::replaceTaxonomyVariables($prompt, $id);
      $prompt = static::replaceCustomFieldVariables($prompt, $id);
    }

    return $prompt;
  }

  public static function replaceCustomFieldVariables(string $prompt, int $postId)
  {
    $regex = '/{{custom_field:(?<name>.*)}}/m';
    preg_match_all($regex, $prompt, $matches, PREG_SET_ORDER, 0);

    foreach ($matches as $match) {
      $field = $match['name'];
      $values = get_post_custom_values($field, $postId);
      if (!$values) continue;

      $values = array_map(function ($value) {
        return unserialize($value) ?: $value;
      }, $values);

      $values = array_map(function ($value) {
        return is_array($value) ? json_encode($value) : $value;
      }, $values);

      $prompt = str_replace($match[0], implode(', ', $values), $prompt);
    }

    //remove any unused taxonomy placeholders
    $prompt = preg_replace($regex, '', $prompt);

    return $prompt;
  }

  public static function replaceTaxonomyVariables(string $prompt, int $postId)
  {
    $regex = '/{{taxonomy:(?<name>.*)}}/m';
    preg_match_all($regex, $prompt, $matches, PREG_SET_ORDER, 0);

    foreach ($matches as $match) {
      $taxonomy = $match['name'];
      $terms = wp_get_post_terms($postId, $taxonomy, ['fields' => 'all']);
      if (is_wp_error($terms)) continue;

      $names = array_map(function ($term) {
        return $term->name;
      }, $terms);

      $prompt = str_replace($match[0], implode(', ', $names), $prompt);
    }

    //remove any unused taxonomy placeholders
    $prompt = preg_replace($regex, '', $prompt);

    return $prompt;
  }

  public static function replaceSiteVariables(string $prompt)
  {
    $variables = [
      'site_title' => get_bloginfo('title'),
      'site_language' => get_bloginfo('language'),
      'site_url' => get_bloginfo('url'),
    ];

    foreach ($variables as $key => $value) {
      $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
    }

    return $prompt;
  }

  public static function replacePostVariables(WP_Post $post, string $prompt)
  {
    if (!$post) {
      return $prompt;
    }

    $replacers = [
      'post_title' => 'PostTitle',
      'post_content' => 'PostContent',
      'post_excerpt' => 'PostExcerpt',
      'post_tags' => 'PostTags',
      'post_categories' => 'PostCategories',
      'post_type' => 'PostType',
    ];

    foreach ($replacers as $key => $method) {
      if (strpos($prompt, '{{' . $key . '}}') !== false) {
        $prompt = static::{"replace$method"}($post, $prompt);
      }
    }

    return $prompt;
  }

  public static function replacePostType(WP_Post $post, string $prompt)
  {
    return str_replace('{{post_type}}', $post->post_type, $prompt);
  }

  public static function replacePostTitle(WP_Post $post, string $prompt)
  {
    return str_replace('{{post_title}}', $post->post_title, $prompt);
  }

  public static function replacePostContent(WP_Post $post, string $prompt)
  {
    $content = PostHelper::getPostContent($post->ID);
    Log::debug('Converting HTML to Markdown', ['post' => substr($content, 0, 100)]);
    return str_replace('{{post_content}}', self::convertHtmlToMarkdown($content), $prompt);
  }

  public static function replacePostExcerpt(WP_Post $post, string $prompt)
  {
    return str_replace('{{post_excerpt}}', $post->post_excerpt, $prompt);
  }

  public static function replacePostTags(WP_Post $post, string $prompt)
  {
    return str_replace('{{post_tags}}', static::getCommaSeparatedTerms($post->ID, 'post_tag'), $prompt);
  }

  public static function replacePostCategories(WP_Post $post, string $prompt)
  {
    return str_replace('{{post_categories}}', static::getCommaSeparatedTerms($post->ID, 'category'), $prompt);
  }

  public static function replaceProductVariables($product, string $prompt)
  {
    $replacers = [
      'product_name' => 'ProductName',
      'product_short_description' => 'ProductShortDescription',
      'product_description' => 'ProductDescription',
      'product_attributes' => 'ProductAttributes',
      'product_tags' => 'ProductTags',
      'product_price' => 'ProductPrice',
      'product_weight' => 'ProductWeight',
      'product_length' => 'ProductLength',
      'product_width' => 'ProductWidth',
      'product_height' => 'ProductHeight',
      'product_sku' => 'ProductSku',
      'product_purchase_note' => 'ProductPurchaseNote',
      'product_categories' => 'ProductCategories',
    ];

    foreach ($replacers as $key => $method) {
      if (strpos($prompt, '{{' . $key . '}}') !== false) {
        $prompt = static::{"replace$method"}($product, $prompt);
      }
    }

    return $prompt;
  }

  public static function replaceProductName($product, string $prompt)
  {
    return str_replace('{{product_name}}', $product->get_name(), $prompt);
  }

  public static function replaceProductShortDescription($product, string $prompt)
  {
    return str_replace('{{product_short_description}}', self::convertHtmlToMarkdown($product->get_short_description()), $prompt);
  }

  public static function replaceProductDescription($product, string $prompt)
  {
    return str_replace('{{product_description}}', self::convertHtmlToMarkdown($product->get_description()), $prompt);
  }

  public static function replaceProductAttributes($product, string $prompt)
  {
    return str_replace('{{product_attributes}}', implode(',', array_map(function ($attribute) {
      return $attribute->get_name();
    }, $product->get_attributes())), $prompt);
  }

  public static function replaceProductTags($product, string $prompt)
  {
    return str_replace('{{product_tags}}', static::getCommaSeparatedTerms($product->get_id(), 'product_tag'), $prompt);
  }

  public static function replaceProductPrice($product, string $prompt)
  {
    return str_replace('{{product_price}}', $product->get_price(), $prompt);
  }

  public static function replaceProductWeight($product, string $prompt)
  {
    return str_replace('{{product_weight}}', $product->get_weight(), $prompt);
  }

  public static function replaceProductLength($product, string $prompt)
  {
    return str_replace('{{product_length}}', $product->get_length(), $prompt);
  }

  public static function replaceProductWidth($product, string $prompt)
  {
    return str_replace('{{product_width}}', $product->get_width(), $prompt);
  }

  public static function replaceProductHeight($product, string $prompt)
  {
    return str_replace('{{product_height}}', $product->get_height(), $prompt);
  }

  public static function replaceProductSku($product, string $prompt)
  {
    return str_replace('{{product_sku}}', $product->get_sku(), $prompt);
  }

  public static function replaceProductPurchaseNote($product, string $prompt)
  {
    return str_replace('{{product_purchase_note}}', $product->get_purchase_note(), $prompt);
  }

  public static function replaceProductCategories($product, string $prompt)
  {
    return str_replace('{{product_categories}}', static::getCommaSeparatedTerms($product->get_id(), 'product_cat'), $prompt);
  }

  public static function getCommaSeparatedTerms(int $id, string $taxonomy)
  {
    $terms = wp_get_post_terms($id, $taxonomy, ['fields' => 'all']);
    $names = array_map(function ($term) {
      return $term->name;
    }, $terms);

    return implode(',', $names);
  }

  private static function convertHtmlToMarkdown(string $html)
  {
    try {
      return self::converter()->convert($html);
    } catch (\InvalidArgumentException $e) {
      Log::error('An error occured when converting HTML to Markdown', [
        'error' => $e,
        'trace' => $e->getTraceAsString(),
        'html' => substr($html, 0, 100),
      ]);

      return $html;
    } catch (\Throwable $th) {
      Log::error('An error occured when converting HTML to Markdown', [
        'error' => $th,
        'trace' => $th->getTraceAsString(),
        'html' => substr($html, 0, 100),
      ]);

      throw $th;
    }
  }

  public static function replaceUserName(string $prompt, string $name)
  {
    return str_replace('{{user_name}}', $name, $prompt);
  }

  public static function replaceUserEmail(string $prompt, string $email)
  {
    return str_replace('{{user_email}}', $email, $prompt);
  }

  private static function converter()
  {
    if (!isset(self::$converter)) {
      self::$converter = new HtmlConverter([
        'strip_tags' => true,
        'hard_break' => true,
        'remove_nodes' => 'script, style',
        'strip_placeholder_links' => true,
      ]);
    }

    return self::$converter;
  }
}

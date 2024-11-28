<?php

namespace AIMuse\Helpers;

use Bricks\Frontend;
use AIMuseVendor\Symfony\Component\HttpFoundation\Request;

class PostHelper
{
  public static bool $isOverride = false;
  public static int $id = 0;

  public static function getPostTypes()
  {
    $excluded = array('attachment', 'revision', 'nav_menu_item');
    $postTypes = get_post_types([
      'public' => true,
      'show_ui' => true,
    ], 'objects');

    $types = [];
    $order = 10;

    foreach ($postTypes as $postType) {
      if (in_array($postType->name, $excluded)) {
        continue;
      }

      $data = [
        'name' => $postType->name,
        'label' => $postType->label,
        'singularLabel' => $postType->labels->singular_name ?? $postType->label,
        'dashicon' => $postType->menu_icon ?? 'dashicons-admin-post',
      ];
      $data['isWooCommerce'] = $postType->name === 'product' && class_exists('WooCommerce');
      $data['isPremium'] = !in_array($postType->name, PremiumHelper::$freePostTypes);

      if ($postType->name === 'post') {
        $data['order'] = 0;
      } elseif ($postType->name === 'page') {
        $data['order'] = 1;
      } elseif ($data['isWooCommerce']) {
        $data['order'] = 2;
      } else {
        $data['order'] = $order++;
      }

      $data['supports'] = [];

      foreach (['title', 'thumbnail', 'excerpt'] as $key) {
        $data['supports'][$key] = post_type_supports($postType->name, $key);
      }

      $data['taxonomies'] = get_object_taxonomies($postType->name);

      $types[] = $data;
    }

    usort($types, function ($a, $b) {
      return $a['order'] <=> $b['order'];
    });

    return $types;
  }

  public static function getPostType()
  {
    $postType = get_post_type();

    if ($postType) return $postType;

    $currentScreen = get_current_screen();

    if (!$currentScreen) return false;
    return $currentScreen->post_type;
  }

  public static function getPostContent($postId)
  {
    if (defined('BRICKS_DB_PAGE_CONTENT')) {
      $bricksEditorMode = get_post_meta($postId, constant('BRICKS_DB_EDITOR_MODE'), true);

      if ($bricksEditorMode === 'bricks') {
        $meta = get_post_meta($postId, BRICKS_DB_PAGE_CONTENT, true);
        return Frontend::render_data($meta);
      }
    }

    return apply_filters('the_content', get_post_field('post_content', $postId));
  }

  public static function overrideURL(string $url)
  {
    /**
     * @var \WP $wp
     */
    global $wp;

    $request = Request::create($url);
    $backup = Request::createFromGlobals();

    $_SERVER['REQUEST_URI'] = $request->server->get('REQUEST_URI');
    $_GET = $request->query->all();
    $_REQUEST = $request->request->all();

    $wp->parse_request();
    $wp->query_posts();
    $wp->register_globals();

    $_SERVER['REQUEST_URI'] = $backup->server->get('REQUEST_URI');
    $_GET = $backup->query->all();
    $_REQUEST = $backup->request->all();

    self::$isOverride = true;
  }

  public static function overrideID(int $id)
  {
    self::$id = $id;
    $url = get_permalink($id);

    self::overrideURL($url);
  }

  public static function getContent()
  {
    if (is_singular()) {
      return self::getPostContent(get_the_ID());
    } elseif (is_archive()) {
      return get_queried_object()->description;
    } elseif (is_home()) {
      return get_post_field('post_content', get_option('page_for_posts'));
    } elseif (is_front_page()) {
      return get_post_field('post_content', get_option('page_on_front'));
    } else {
      return '';
    }
  }

  public static function getTitle()
  {
    if (is_singular()) {
      return get_the_title();
    } elseif (is_archive()) {
      return get_queried_object()->name;
    } elseif (is_home()) {
      return get_the_title(get_option('page_for_posts'));
    } elseif (is_front_page()) {
      return get_the_title(get_option('page_on_front'));
    } else {
      return '';
    }
  }
}

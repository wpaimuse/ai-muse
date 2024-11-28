<?php

namespace AIMuseVendor\Illuminate\Http;

use AIMuseVendor\Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class File extends SymfonyFile
{
    use FileHelpers;
}

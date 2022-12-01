<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Facade;

class ImageService extends Facade
{
    /**
     * @var string
     */
    private $url;

    public function __construct()
    {
        $this->url = env("APP_URL") . '/storage/';
    }

    public function image($image)
    {
        $image = $this->url . $image;
        $image = str_replace('\\', '/', $image);
        return $image;
    }

    public function multiimage($image)
    {
        $return = [];
        if ($image) {
            foreach ($image as $value) {
                $temp = $this->url . $value;
                $return[] = str_replace('\\', '/', $temp);
            }
        } else {
            $return = [];
        }
        return $return;
    }

}

?>

<?php

namespace App\Services;

use App\Models\FavoriteItemsUser;
use App\Models\Item;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ItemService
{

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function item($where = '')
    {
        $item = Item::leftjoin('categories', 'categories.id', '=', 'items.category')
            ->leftjoin('sub_categories', 'sub_categories.id', '=', 'items.subcategory')
            ->leftjoin('types', 'types.id', '=', 'items.type')
            ->select('items.id', 'items.count', 'items.name', 'items.image', 'items.content', 'categories.name as category',
                'sub_categories.name as subcategory', 'categories.id as categoryid', 'sub_categories.id as subcategoryid',
                'sub_categories.color as category_color', 'types.image as type_image', 'items.is_new', 'items.price');
        return $item;
    }

    public function image_item($item, $type = 0)
    {
        if ($item) {
            if ($type == 1) {
                foreach ($item as $block) {
                    $block->image = $this->imageService->image($block->image);
                    $block->type_image = $this->imageService->image($block->type_image);
                }
            } else {
                $item->image = $this->imageService->image($item->image);
                $item->type_image = $this->imageService->image($item->type_image);
            }
        }
        return $item;
    }

    public function favorite($items)
    {
        if (isset(request()->header()['token'][0]) && !Auth::check()) {
            $token = request()->header()['token'][0];
            $user = User::where('api_token', 'LIKE', trim($token))->first();
            if ($user) {
                Auth::login($user);
            }
        }
        foreach ($items as $item) {
            $item->isFavorite = $this->favoriteSingle($item);
        }
        return $items;
    }

    public function favoriteSingle($item)
    {
        $favorite = null;
        if (Auth::check()) {
            $favorite = FavoriteItemsUser::query()->where('user_id', Auth::id())->where('item_id', $item->id)->first();
        }
        return $favorite ? 1 : 0;
    }

    public function get_item()
    {
        $item = $this->item()->get();
        if ($item) {
            $item = $this->image_item($item, 1);
        }
        return $item;
    }

    public function first_item()
    {
        $item = $this->item()->first();
        if ($item) {
            $item = $this->image_item($item);
            return $item;
        }
        return null;
    }
}

?>

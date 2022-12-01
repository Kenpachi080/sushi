<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FavoriteItemsUser;
use App\Models\Item;
use App\Models\Mostcategoryie;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ItemService;
use Illuminate\Support\Facades\Auth;


class ItemController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/item/",
     * summary="Поиск товаров",
     * description="Поиск товаров",
     * operationId="itemView",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="id", type="string", format="string", example="1"),
     *       @OA\Property(property="category", type="string", format="string", example="1"),
     *       @OA\Property(property="subcategory", type="string", format="string", example="1"),
     *       @OA\Property(property="search", type="string", format="string", example="1"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Вощврощаются товары",
     *    @OA\JsonContent(
     *       type="object",
     *             @OA\Property(
     *                property="user",
     *                type="object",
     *               example={
     *                  }
     *              ),
     *     @OA\Property(
     *                property="token",
     *                type="string",
     *               example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */

    public function item(ItemService $itemService, Request $request)
    {
        /* REQUEST
         category, subcategory, id

          */
        if ($request->id) {
            $item = $itemService->item()->where('items.id', '=', $request->id)->first();
            if ($item) {
                if (isset(request()->header()['token'][0]) && !Auth::check()) {
                    $token = request()->header()['token'][0];
                    $user = User::where('api_token', 'LIKE', trim($token))->first();
                    if ($user) {
                        Auth::login($user);
                    }
                }
                $item->isFavorite = $itemService->favoriteSingle($item);
                $return = $itemService->image_item($item);
            }
            return response($return, 200);
        }

        $item = $itemService->item();
        if ($request->category) {
            $item->where('categoryid', '=', $request->category);
        }
        if ($request->subcategory) {
            $item->where('subcategoryid', '=', $request->subcategory);
        }
        if ($request->search) {
            $item->where('items.name', 'LIKE', "%$request->search%");
        }
        $return = $itemService->favorite(
            $itemService->image_item($item->get(), 1)
        );
        return response($return, 200);
    }

    /**
     * @OA\Get(path="/api/item/full",
     *   tags={"item"},
     *   operationId="viewItem",
     *   summary="Вытащить все товары",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается все товары",
     *   )
     * )
     */

    public function full(ItemService $itemService)
    {
        $categories = Category::all();
        foreach ($categories as $category) {
            $mostCategories = Mostcategoryie::leftjoin('sub_categories', 'sub_categories.id', '=', 'mostcategoryies.sub_category_id')
                ->where('category_id', '=', $category->id)
                ->select('sub_categories.*')
                ->get();
            foreach ($mostCategories as $subcategory) {
                $items = $itemService
                    ->item()
                    ->where('items.subcategory', '=', $subcategory->id)
                    ->where('items.category', '=', $category->id)
                    ->get();;
                $items = $itemService->favorite(
                    $itemService->image_item($items, 1)
                );
                $subcategory->items = $items;
            }
            $item = $itemService
                ->item()
                ->where('items.category', '=', $category->id)
                ->whereNull('items.subcategory')
                ->get();
            $item = $itemService->favorite(
                $itemService->image_item($item, 1)
            );
            $category->nocategory = $item;
            $category->subcategory = $mostCategories;
        }

        return response($categories, 200);
    }

    public function addFavorite($id)
    {
        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Товар не был найден'], 404);
        }
        FavoriteItemsUser::query()->firstOrCreate(['item_id' => $id, 'user_id' => Auth::id()]);

        return response()->json(['message' => 'Товар добавлен в избранное'], 201);
    }

    public function deleteFavorite($id)
    {
        $favorite = FavoriteItemsUser::query()
            ->where('item_id', $id)
            ->where('user_id', Auth::id())
            ->first();
        if (!$favorite) {
            return response()->json(['message' => 'Товар не был найден'], 404);
        }

        $favorite->delete();
        return response()->json(['message' => 'Товар успешно удален из избранного'], 202);
    }

    public function category()
    {
        return response(Category::all(), 200);
    }
}

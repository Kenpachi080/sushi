<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\NotFoundItemResource;
use App\Http\Resources\NotOrderResource;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\TypeDelivery;
use App\Models\TypePayment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Services\ImageService;
use App\Services\ItemService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * @OA\Get(path="/api/order/help",
     *   tags={"order"},
     *   operationId="orderHelp",
     *   summary="Типы для заказов",
     * @OA\Response(
     *    response=200,
     *    description="Типы для заказов",
     *   )
     * )
     */
    public function help()
    {
        $delivery = TypeDelivery::all();
        $pay = TypePayment::all();
        $return = [
            'delivery' => $delivery,
            'payment' => $pay
        ];
        return response($return, 200);
    }


    public function create(CreateOrderRequest $request, ImageService $imageService)
    {
        if (gettype($request->address) == 'array') {
            $geo = json_decode(json_encode(simplexml_load_string($this->map($request->address[0], $request->address[1]))), TRUE);
            /*
             * короче, мы обращаемся в яндекс за данными, но они приходят в xml, из хмл мы в джсон, а потом джсон
             * в АССОЦИАТИВНЫЙ массив. Понимаю, можно и лучше написать, но я оставил этот звездный час для тебя
             *  */
            $address = $geo['GeoObjectCollection']['featureMember'][0]['GeoObject']['name'];
        } else {
            $address = $request->address;
        }
        $userID = null;
        if (isset($request->api_token) || isset($request->header()['token'][0])) {
            $token = $request->api_token ?? $request->header()['token'][0];
            $user = User::where('api_token', 'LIKE', $token)->first();
            if ($user) {
                $userID = $user->id;
                $userOrder = Order::where('user', '=', $userID)->first();
                if (!$userOrder && $address && $request->flat) {
                    $user->address = $address;
                    $user->flat = $request->flat;
                    $user->save();
                }
            }
        }
        $order = Order::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'type_delivery' => $request->type_delivery,
            'address' => $address,
            'flat' => $request->flat,
            'type_pay' => $request->type_pay,
            'odd_money' => $request->odd_money,
            'person' => $request->person,
            'comment' => $request->comment,
            'user' => $userID
        ]);

        $items = [];
        $price = 0;

        foreach ($request->items as $item) {
            $leftitem = Item::where('id', '=', $item['id'])->first();
            if (!$leftitem) {
                continue;
            }
            $order_item = OrderItem::create([
                'order' => $order->id,
                'item' => $item['id'],
                'count' => $item['count'],
            ]);
            $item_price = $leftitem->price * $item['count'];
            $price += $item_price;
            $leftitem->image = $imageService->image($leftitem->image);
            $items[] = $leftitem;
        }

        if (count($items) < 1) {
            $order->delete();
            return response(new NotFoundItemResource($request), 404);
        }

        $delivery = TypeDelivery::where('id', '=', $request->type_delivery)->first();

        if ($delivery->flag > $price) {
            $price += $delivery->pricedelivery;
        }

        $order->price = $price;
        $order->save();
        $order->items = $items;

        return response($order, 201);
    }

    /**
     * @OA\Post(
     * path="/api/order/view",
     * summary="Просмотр заказов",
     * description="Просмотр заказов",
     * operationId="orderView",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token"},
     *       @OA\Property(property="api_token", type="string", format="string", example="123"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Возврощается заказы",
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
    public function view(Request $request, ItemService $itemService)
    {
        $order = Order::where('user', '=', Auth::id())->get();
        if (count($order) < 1) {
            return response(new NotOrderResource($request), 404);
        }
        foreach ($order as $block) {
            $items = OrderItem::where('order', '=', $block->id)->get();
            $item = [];
            foreach ($items as $res) {
                $preItem = $itemService->image_item(
                    $itemService->item()
                        ->where('items.id', '=', $res->item)
                        ->first()
                );
                if ($preItem) {
                    $preItem->kolvo = $res->count;
                }
                $item[] = $preItem;
            }
            $block->items = $item;
            $status = OrderStatus::leftjoin('type_statuses', 'type_statuses.id', '=', 'order_statuses.status')
                ->where('order', '=', $block->id)
                ->select('type_statuses.name', 'type_statuses.color', 'order_statuses.id', 'order_statuses.created_at')
                ->get();
            $block->status = $status;
        }

        return response($order, 200);
    }


    public function map($dolg, $shir)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://geocode-maps.yandex.ru/1.x/?apikey=6bf6ba2d-2cd2-4d47-b9ab-6580cd082c31&geocode=$dolg,$shir",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Cookie: _yasc=z9cc2HoaOlW2X9UluZks2NymegwneAPzD29MaA8nuVrEJ2o0NRDu920pntvc; i=/zlSgMaxiLa/TuWDCpvRZKiW+VJpyfGJVUXZDwxl4z4cQDDn6S8mJyIuzq+0SfwQGE70KT6VnpNuWopz2QLS5xhoB2w='
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

}

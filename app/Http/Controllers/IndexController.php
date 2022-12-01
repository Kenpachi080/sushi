<?php

namespace App\Http\Controllers;


use App\Http\Requests\ApplicationRequest;
use App\Models\Application;
use App\Models\Cafe;
use App\Models\Category;
use App\Models\Contact;
use App\Models\CreateOrder;
use App\Models\DeliveryBlock;
use App\Models\DeliveryStep;
use App\Models\DeliveryTerm;
use App\Models\DeliveryType;
use App\Models\Recomendation;
use App\Models\Slider;
use App\Models\TypeDelivery;
use App\Models\TypePayment;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\AuthService;
use App\Services\ImageService;
use App\Services\ItemService;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var ImageService
     */
    private $image;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->url = env("APP_URL") . '/storage/';
    }

    public function test()
    {
        $user = User::query()->where('id', 10)->first()->favorite()->get();
        return ['user' => $user];
    }

    /**
     * @OA\Get(path="/api/index",
     *   tags={"view"},
     *   operationId="viewIndex",
     *   summary="Информация про сайт",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function index(ImageService $imageService, ItemService $itemService)
    {
        $slider = Slider::all();
        foreach ($slider as $block) {
            $block->images = $imageService->image($block->images);
        }
        $category = Category::all();
        foreach ($category as $block) {
            $block->items = $itemService->get_item()->where('categoryid', '=', $block->id)->take(8);
        }

        $return = [
            'slider' => $slider,
            'item' => $category
        ];
        return response($return, 200);
    }

    /**
     * @OA\Get(path="/api/",
     *   tags={"view"},
     *   operationId="viewTitle",
     *   summary="Настройки сайта",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function title()
    {
        return response([
            'menu' => $this->url . json_decode(setting('site.menu'))[0]->download_link,
            'logo' => $this->url . setting('site.logo'),
            'phone1' => setting('site.phone1'),
            'phone2' => setting('site.phone2'),
            'phone3' => setting('site.phone3'),
            'instagram' => setting('site.instagram'),
            'vk' => setting('site.vk'),
            'facebook' => setting('site.facebook'),
            'googleplay' => setting('site.googleplay'),
            'appstore' => setting('site.appstore'),
            'pickup' => setting('site.pickup'),
            'address' => setting('site.address'),
            'delivery' => setting('site.delivery'),
            'order' => setting('site.order'),
            'footer' => setting('site.footer'),
            'life_address' => setting('site.life_address'),
            'end_time' => setting('site.time'),
        ], 200);
    }

    /**
     * @OA\Get(path="/api/cafe",
     *   tags={"view"},
     *   operationId="viewCafe",
     *   summary="Cafe",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function cafe(ImageService $imageService)
    {
        $cafe = Cafe::first();
        $cafe->images = $imageService->multiimage(json_decode($cafe->images));
        return response($cafe, 200);
    }

    /**
     * @OA\Get(path="/api/contact",
     *   tags={"view"},
     *   operationId="viewcontact",
     *   summary="Contact",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function contact(ImageService $imageService)
    {
        $contact = Contact::all();
        foreach ($contact as $block) {
            $block->image = $imageService->image($block->image);
        }
        return response($contact, 200);
    }

    /**
     * @OA\Post(
     * path="/api/",
     * summary="Подать заявку",
     * description="Подать заявку",
     * operationId="sendApplication",
     * tags={"view"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"name, email, contents"},
     *       @OA\Property(property="name", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="123"),
     *       @OA\Property(property="contents", type="string", format="string", example="12"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function application(ApplicationRequest $request)
    {
        $application = Application::create([
            'name' => $request->name,
            'email' => $request->email,
            'content' => $request->contents
        ]);
        return response($application, 200);
    }

    /**
     * @OA\Get(path="/api/delivery",
     *   tags={"view"},
     *   operationId="viewDelivery",
     *   summary="Delivery",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function delivery(ImageService $imageService)
    {
        $deliveryMain = DB::table('deliveries_main')->get();
        foreach ($deliveryMain as $delivery) {
            $delivery->image = $imageService->image($delivery->image);
        }
        $deliveryBlock = DeliveryBlock::all();
        foreach ($deliveryBlock as $delivery) {
            $delivery->image = $imageService->image($delivery->image);
        }
        $deliveryStep = DeliveryStep::all();
        $deliveryType = DeliveryType::all();
        $delivery = DeliveryTerm::all();
        foreach ($delivery as $block) {
            $block->logo = $imageService->image($block->logo);
        }
        $date = [
            'main' => $deliveryMain,
            'Block' => $deliveryBlock,
            'step' => $deliveryStep,
            'type' => $deliveryType,
            'term' => $delivery
        ];
        return response($date, 200);
    }

    /**
     * @OA\Get(path="/api/recommendation",
     *   tags={"view"},
     *   operationId="viewRecommendation",
     *   summary="Рекомендация при создании заказа",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function recommendation()
    {
        $recomendation = Recomendation::leftjoin('items', 'items.id', '=', 'recomendations.itemid')
            ->select('items.*')
            ->get();
        return response($recomendation, 200);
    }

    /**
     * @OA\Get(path="/api/vacancy",
     *   tags={"view"},
     *   operationId="viewVacancy",
     *   summary="Вакансии",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function vacancy()
    {
        return response(Vacancy::all(), 200);
    }

    /**
     * @OA\Get(path="/api/ordercreate",
     *   tags={"view"},
     *   operationId="viewOrderCreate",
     *   summary="Создание заказов",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function ordercreate()
    {
        $return = [
            'create_order' => CreateOrder::all(),
            'type_delivery' => TypeDelivery::all(),
            'type_payment' => TypePayment::all(),
        ];
        return response($return, 200);
    }

}

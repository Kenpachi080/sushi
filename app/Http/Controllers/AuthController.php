<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangeRequest;
use App\Http\Requests\CodeRequest;
use App\Http\Requests\ForgotRequest;
use App\Mail\ForgotMail;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RebootPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\BadPasswordResource;
use App\Http\Resources\ChangeDataResource;
use App\Http\Resources\NotFoundUserResourse;
use App\Http\Resources\RebootPasswordResource;
use App\Http\Resources\SendMessageResource;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Resources\UserResource;
use App\Http\Resources\NotFoundCodeResourse;

class AuthController extends Controller
{

    /**
     * @var AuthService
     */
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->url = env("APP_URL") . '/storage/';
    }

    /**
     * @OA\Post(
     * path="/api/auth/register",
     * summary="Регистрация",
     * description="Регистрация",
     * operationId="authRegister",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Регистрация",
     *    @OA\JsonContent(
     *       required={"phone, password, email"},
     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="testemail@mail.ru"),
     *    ),
     * ),
     *
     * @OA\Response(
     *    response=201,
     *    description="Возврощается полная информация про пользователя, и его токен для дальнейшей работы с юзером",
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
     *               example="18|TuQoXj84z5IxclUeRK89bSS4839sQfJ8KsQRVRVO",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->phone,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        $token = $this->authService->token();
        $user->api_token = $token;
        $user->save();
        return response(new UserResource($user), 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/login",
     * summary="Авторизация",
     * description="Авторизация по АПИ токену",
     * operationId="authLogin",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"phone, password"},
     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Возврощается полная информация про пользователя, и его токен для дальнейшей работы с юзером",
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
    public function login(LoginRequest $request)
    {
        $user = null;
        if ($request->phone || $request->email) {
        $user = User::query()
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->first();
        }
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response(new BadPasswordResource($request), 401);
        }
        return response(new UserResource($user), 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/rebootpassword",
     * summary="Поменять пароль",
     * description="Поменять пароль",
     * operationId="rebootpassword",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"oldpassword, newpassword"},
     *       @OA\Property(property="oldpassword", type="string", format="string", example="123"),
     *       @OA\Property(property="newspassword", type="string", format="string", example="321"),
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="CallBack с статусом",
     *    @OA\JsonContent(
     *       type="object",
     *     @OA\Property(
     *                property="message",
     *                type="string",
     *               example="Пароль был успешно изменен",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function rebootpassword(RebootPasswordRequest $request)
    {
        $user = auth()->user();
        if (!$user || !Hash::check($request->oldpassword, $user->password)) {
            return response(new BadPasswordResource($request), 401);
        }
        $user->password = bcrypt($request->newspassword);
        $user->save();
        return response(new RebootPasswordResource($request), 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/change",
     * summary="Поменять данные клиента",
     * description="Поменять данные клиента",
     * operationId="authChange",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"fio, email, phone, api_token"},
     *       @OA\Property(property="fio", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="CallBack с статусом",
     *    @OA\JsonContent(
     *       type="object",
     *             @OA\Property(
     *                property="user",
     *                type="object",
     *               example={
     *                  }
     *              ),
     *     @OA\Property(
     *                property="message",
     *                type="string",
     *               example="Данные успешно были изменены",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function change(ChangeRequest $request)
    {
        $data = $request->validated();
        auth()->user()->update($data);
        return response(new ChangeDataResource($request), 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/forgot",
     * summary="Забыл пароль",
     * description="забыл пароль",
     * operationId="forgot",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"email, phone"},
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="На почту был отправлен код",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function forgot(ForgotRequest $request)
    {
        $user = User::query()
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->firstOrFail();
        $code = Str::random(6);
        $user->code = $code;
        $user->save();
        if (strpos($user->email, '@')) {
            Mail::to($user->email)->send(new ForgotMail($code));
            return response(new SendMessageResource($user), 200);
        }
        return response(['message' => 'Email неправильный'], 409);
    }

    /**
     * @OA\Post(
     * path="/api/auth/code",
     * summary="Подтвердить код",
     * description="Подтвердить код",
     * operationId="code",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"email, phone, code"},
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *       @OA\Property(property="code", type="string", format="string", example=""),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Правильный код",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function code(CodeRequest $request)
    {

        $user = User::query()
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->firstOrFail();
        if ($user->code == $request->code) {
            return response(['message' => 'Правильный код'], 200);
        } else {
            return response(new NotFoundCodeResourse($request), 404); // not
        }
    }

    /**
     * @OA\Post(
     * path="/api/auth/changePassword",
     * summary="Помменять пароль",
     * description="Помменять пароль",
     * operationId="changePassword",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"password, email, phone, address"},
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *       @OA\Property(property="code", type="string", format="string", example=""),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с товаром",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::query()
            ->when($request->email, fn($query) => $query->where('email', $request->email))
            ->when($request->phone, fn($query) => $query->where('phone', $request->phone))
            ->firstOrFail();
        if (!$user->code == $request->code) {
            return response(new NotFoundCodeResourse($request), 404);
        }
        $user->password = bcrypt($request->password);
        $user->code = '';
        $user->save();
        return response(new RebootPasswordResource($request), 200);
    }

    /**
     * @OA\Post(
     * path="/api/auth/view",
     * summary="Посмотреть данные",
     * description="Посмотреть данные",
     * operationId="viewauth",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token"},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с данными",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function view()
    {
        return response(new UserResource(Auth::id()), 200);
    }

    /**
     * @OA\Post(
     * path="/api/auth/address",
     * summary="Поменять адресные данные",
     * description="Поменять адресные данные",
     * operationId="addressauth",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="address", type="string", format="string", example=""),
     *       @OA\Property(property="flat", type="string", format="string", example=""),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с данными",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function address(AddressRequest $request)
    {
        try {
            $data = $request->validated();
            auth()->user()->update($data);
            return response(['message' => 'Данные успешно сохранены'], 201);
        } catch (\Exception $exception) {
            return response(['message' => 'Произошла ошибка'], 409);
        }
    }

    private function date_normalise($date, $format)
    {
        $res = date($format, $date);
        return $res->format($format);
    }
}

?>

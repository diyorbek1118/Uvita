backend/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   │   ├── ForceJsonResponse.php
│   │   │   ├── EnsureIsManager.php     # role.manager — auth('sanctum')->check() → role → is_active
│   │   │   ├── EnsureIsCourier.php     # role.courier
│   │   │   ├── EnsureIsAdmin.php       # role.admin (admin|super_admin)
│   │   │   └── EnsureIsSuperAdmin.php  # role.super_admin
│   │   └── Requests/
│   │       └── BaseRequest.php
│   ├── Jobs/
│   │   ├── SendSmsJob.php              # ShouldQueue — SmsService::send() async
│   │   ├── SendTelegramJob.php         # ShouldQueue — TelegramService::send() async
│   │   └── ClearCartJob.php            # ShouldQueue — buyurtma yaratilganda savatni tozalaydi
│   ├── Providers/
│   │   └── AppServiceProvider.php      # loadModuleMigrations() glob
│   │                                   # Bindings: User, OtpAttempt, TokenService, Category, Product, Cart, Order, Payment, Review
│   └── Shared/
│       ├── Exceptions/
│       │   └── DomainException.php
│       ├── Responses/
│       │   └── ApiResponse.php
│       └── Services/
│           ├── SMS/
│           │   └── SmsService.php      # send(): void — Log::info() mock
│           └── Telegram/
│               └── TelegramService.php # send(): void — Log::info() mock (TODO: Bot API)
│
├── Modules/
│   │
│   ├── Auth/                           # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── OtpAttempt.php
│   │   │   ├── Exceptions/
│   │   │   │   ├── InvalidOtpException.php
│   │   │   │   └── OtpRateLimitException.php
│   │   │   ├── Repositories/
│   │   │   │   └── OtpAttemptRepositoryInterface.php
│   │   │   └── ValueObjects/
│   │   │       └── PhoneNumber.php
│   │   ├── Application/
│   │   │   ├── Commands/
│   │   │   │   ├── SendOtpCommand.php
│   │   │   │   └── VerifyOtpCommand.php
│   │   │   ├── Contracts/
│   │   │   │   └── TokenServiceInterface.php
│   │   │   ├── DTOs/
│   │   │   │   ├── SendOtpDTO.php
│   │   │   │   └── VerifyOtpDTO.php
│   │   │   └── Handlers/
│   │   │       ├── SendOtpHandler.php
│   │   │       └── VerifyOtpHandler.php
│   │   ├── Infrastructure/
│   │   │   ├── Auth/
│   │   │   │   └── SanctumTokenService.php
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   ├── 2026_06_18_092439_create_personal_access_tokens_table.php
│   │   │       │   └── 2026_06_22_000001_create_otp_attempts_table.php
│   │   │       ├── Models/
│   │   │       │   └── OtpAttempt.php
│   │   │       └── Repositories/
│   │   │           └── EloquentOtpAttemptRepository.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── AuthController.php
│   │       ├── Requests/
│   │       │   ├── SendOtpRequest.php
│   │       │   └── VerifyOtpRequest.php
│   │       └── routes/
│   │           └── api.php             # POST auth/otp/send | POST auth/otp/verify | POST auth/logout
│   │
│   ├── Category/                       # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Category.php        # create(), modify()
│   │   │   └── Repositories/
│   │   │       └── CategoryRepositoryInterface.php   # findById, save, delete, slugExists
│   │   ├── Application/
│   │   │   ├── Commands/
│   │   │   │   ├── CreateCategoryCommand.php
│   │   │   │   ├── UpdateCategoryCommand.php         # id + dto
│   │   │   │   └── DeleteCategoryCommand.php         # id
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateCategoryDTO.php             # name, slug(auto), image, parentId
│   │   │   │   └── UpdateCategoryDTO.php             # + isActive
│   │   │   ├── Handlers/
│   │   │   │   ├── CreateCategoryHandler.php         # → CategoryModel (201)
│   │   │   │   ├── UpdateCategoryHandler.php         # → CategoryModel (200)
│   │   │   │   ├── DeleteCategoryHandler.php         # → void
│   │   │   │   ├── GetCategoryListHandler.php        # Eloquent paginator → (200)
│   │   │   │   └── GetCategoryByIdHandler.php        # CategoryModel::findOrFail → (200)
│   │   │   └── Queries/
│   │   │       ├── GetCategoryListQuery.php          # perPage, parentId, fromRequest()
│   │   │       └── GetCategoryByIdQuery.php          # id
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   └── 2026_06_22_000002_create_categories_table.php
│   │   │       ├── Models/
│   │   │       │   └── Category.php    # parent(), children() relations
│   │   │       └── Repositories/
│   │   │           └── EloquentCategoryRepository.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── CategoryController.php  # index|show|store|update|destroy
│   │       ├── Requests/
│   │       │   ├── CreateCategoryRequest.php
│   │       │   └── UpdateCategoryRequest.php
│   │       ├── Resources/
│   │       │   └── CategoryResource.php
│   │       └── routes/
│   │           └── api.php             # GET categories (public) | POST/PUT/DELETE (auth:sanctum, role.admin)
│   │
│   ├── Product/                        # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Product.php         # create(), approve(), reject(), modify()
│   │   │   ├── Enums/
│   │   │   │   └── ProductStatusEnum.php  # Active, Inactive, Rejected
│   │   │   ├── Exceptions/
│   │   │   │   └── InsufficientStockException.php
│   │   │   └── Repositories/
│   │   │       └── ProductRepositoryInterface.php    # findById, save, delete, slugExists
│   │   ├── Application/
│   │   │   ├── Commands/
│   │   │   │   ├── CreateProductCommand.php
│   │   │   │   ├── UpdateProductCommand.php          # id + dto
│   │   │   │   ├── DeleteProductCommand.php          # id
│   │   │   │   ├── ApproveProductCommand.php         # id
│   │   │   │   └── RejectProductCommand.php          # id + reason
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateProductDTO.php              # name, slug(auto), desc, price, stock, images, categoryId, managerId
│   │   │   │   └── UpdateProductDTO.php
│   │   │   ├── Handlers/
│   │   │   │   ├── CreateProductHandler.php          # → ProductModel (201)
│   │   │   │   ├── UpdateProductHandler.php          # → ProductModel (200)
│   │   │   │   ├── DeleteProductHandler.php          # → void (soft delete)
│   │   │   │   ├── ApproveProductHandler.php         # → ProductModel (200)
│   │   │   │   ├── RejectProductHandler.php          # → ProductModel (200)
│   │   │   │   ├── GetProductListHandler.php         # spatie QueryBuilder, faqat active
│   │   │   │   └── GetProductByIdHandler.php         # faqat active, findOrFail
│   │   │   └── Queries/
│   │   │       ├── GetProductListQuery.php           # perPage
│   │   │       └── GetProductByIdQuery.php           # id
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   └── 2026_06_22_000003_create_products_table.php
│   │   │       ├── Models/
│   │   │       │   └── Product.php     # SoftDeletes, status+rating+reviews_count casts, category() relation
│   │   │       └── Repositories/
│   │   │           └── EloquentProductRepository.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── ProductController.php  # index|show|store|update|destroy|approve|reject
│   │       ├── Requests/
│   │       │   ├── CreateProductRequest.php
│   │       │   ├── UpdateProductRequest.php
│   │       │   └── RejectProductRequest.php
│   │       ├── Resources/
│   │       │   └── ProductResource.php    # category: {id, name, slug} via whenLoaded
│   │       └── routes/
│   │           └── api.php             # GET public | POST/PUT (role.manager) | DELETE (role.admin) | PUT admin/products/{id}/approve|reject (role.admin)
│   │
│   ├── User/                           # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── User.php
│   │   │   └── Repositories/
│   │   │       └── UserRepositoryInterface.php
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   └── 0001_01_01_000000_create_users_table.php
│   │   │       ├── Models/
│   │   │       │   └── User.php
│   │   │       └── Repositories/
│   │   │           └── EloquentUserRepository.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── UserController.php    # profile() → 200 | update(name) → 200
│   │       ├── Requests/
│   │       │   └── UpdateProfileRequest.php  # name(required, min:2, max:100)
│   │       ├── Resources/
│   │       │   └── UserResource.php      # id, name, phone, created_at
│   │       └── routes/
│   │           └── api.php               # GET|PUT user/profile (auth:api)
│   │
│   ├── Cart/                           # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Cart.php            # addItem(), removeItem(), clear(), totalPrice()
│   │   │   │   └── CartItem.php        # final readonly — id, cartId, productId, quantity, price
│   │   │   ├── Exceptions/
│   │   │   │   ├── CartItemNotFoundException.php     # extends DomainException
│   │   │   │   └── InsufficientStockException.php    # extends DomainException
│   │   │   └── Repositories/
│   │   │       └── CartRepositoryInterface.php       # findByUserId(?Cart), save(Cart):void, clear(int):void
│   │   ├── Application/
│   │   │   ├── Commands/
│   │   │   │   ├── AddItemCommand.php                # userId, productId, quantity; fromRequest()
│   │   │   │   ├── RemoveItemCommand.php             # userId, productId; fromRequest()
│   │   │   │   └── ClearCartCommand.php              # userId
│   │   │   ├── Handlers/
│   │   │   │   ├── AddItemHandler.php                # → CartModel (validates stock, merges qty)
│   │   │   │   ├── RemoveItemHandler.php             # → CartModel (throws ModelNotFoundException)
│   │   │   │   ├── ClearCartHandler.php              # → ?CartModel (null if no cart)
│   │   │   │   └── GetCartHandler.php                # → ?CartModel
│   │   │   └── Queries/
│   │   │       └── GetCartQuery.php                  # userId
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   ├── 2026_06_23_000001_create_carts_table.php
│   │   │       │   └── 2026_06_23_000002_create_cart_items_table.php  # UNIQUE(cart_id, product_id)
│   │   │       ├── Models/
│   │   │       │   ├── CartModel.php                 # table: carts, hasMany(CartItemModel)
│   │   │       │   └── CartItemModel.php             # table: cart_items, belongsTo Cart+Product
│   │   │       └── Repositories/
│   │   │           └── EloquentCartRepository.php    # firstOrCreate cart, whereNotIn delete, updateOrCreate items
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── CartController.php                # index|add|remove|clear
│   │       ├── Requests/
│   │       │   ├── AddItemRequest.php                # product_id(exists), quantity(1-100)
│   │       │   └── RemoveItemRequest.php             # product_id(exists)
│   │       ├── Resources/
│   │       │   └── CartResource.php                  # null guard → empty cart; items, total, delivery_price, grand_total
│   │       └── routes/
│   │           └── api.php             # GET|POST|DELETE cart (auth:api)
│   │
│   ├── Order/                          # ✅ DDD to'liq
│   │   ├── Domain/
│   │   │   ├── Enums/
│   │   │   │   └── OrderStatus.php             # PENDING,PAID,CONFIRMED,READY_TO_DELIVER,DELIVERING,DELIVERED,CANCELLED,DELIVERY_ISSUE
│   │   │   ├── ValueObjects/
│   │   │   │   ├── DeliveryAddress.php          # region,district,street,house,landmark; toArray/fromArray
│   │   │   │   ├── DeliveryTime.php             # value (non-empty string)
│   │   │   │   └── Money.php                    # amount(int); add(), multiply()
│   │   │   ├── Entities/
│   │   │   │   ├── Order.php                    # status machine: markAsPaid/confirm/markReadyToDeliver/markDelivering/markDelivered/incrementNotFound/cancel
│   │   │   │   └── OrderItem.php                # final readonly; subtotal(): Money
│   │   │   ├── Exceptions/
│   │   │   │   ├── InvalidStatusTransitionException.php  # extends DomainException → 422
│   │   │   │   ├── CannotCancelOrderException.php        # extends DomainException → 422
│   │   │   │   └── InsufficientStockException.php        # extends DomainException → 422
│   │   │   └── Repositories/
│   │   │       └── OrderRepositoryInterface.php  # findById,findByUserId,findByStatus,save,findPaidOrders,findReadyToDeliverOrders
│   │   ├── Application/
│   │   │   ├── DTOs/
│   │   │   │   └── CreateOrderDTO.php           # userId,items,address,phone,deliveryTime,courierNote,paymentMethod
│   │   │   ├── Commands/
│   │   │   │   ├── CreateOrderCommand.php        # wraps CreateOrderDTO; fromRequest()
│   │   │   │   ├── ConfirmOrderCommand.php       # orderId
│   │   │   │   ├── ReadyToDeliverCommand.php     # orderId, courierNote(nullable)
│   │   │   │   ├── MarkDeliveringCommand.php     # orderId, courierId
│   │   │   │   ├── MarkDeliveredCommand.php      # orderId
│   │   │   │   ├── NotFoundCommand.php           # orderId, reason
│   │   │   │   ├── CancelOrderCommand.php        # orderId, userId
│   │   │   │   ├── AssignCourierCommand.php      # orderId, courierId (admin)
│   │   │   │   └── DeliveryIssueResolveCommand.php  # orderId, action('reschedule'|'cancel')
│   │   │   ├── Queries/
│   │   │   │   ├── GetOrderByIdQuery.php         # orderId, userId (customer)
│   │   │   │   ├── GetMyOrdersQuery.php          # userId
│   │   │   │   ├── GetPaidOrdersQuery.php        # manager uchun (status=paid)
│   │   │   │   ├── GetAdminOrdersQuery.php       # admin — barcha buyurtmalar
│   │   │   │   └── GetCourierOrdersQuery.php     # courierId — assigned orders
│   │   │   └── Handlers/
│   │   │       ├── CreateOrderHandler.php        # lockForUpdate; CreatePaymentHandler(sync); ClearCartJob; SMS+Telegram; setAttribute(payment_url); → OrderModel (201)
│   │   │       ├── ConfirmOrderHandler.php       # PAID→CONFIRMED; SMS; → OrderModel
│   │   │       ├── ReadyToDeliverHandler.php     # CONFIRMED→READY_TO_DELIVER; Telegram; → OrderModel
│   │   │       ├── MarkDeliveringHandler.php     # courierId set; READY/ISSUE→DELIVERING; SMS; → OrderModel
│   │   │       ├── MarkDeliveredHandler.php      # DELIVERING→DELIVERED; SMS+Telegram; → OrderModel
│   │   │       ├── NotFoundHandler.php           # incrementNotFound (3→DELIVERY_ISSUE); SMS+Telegram; → OrderModel
│   │   │       ├── CancelOrderHandler.php        # PENDING→CANCELLED; ownership check; SMS; → OrderModel
│   │   │       ├── AssignCourierHandler.php      # courierId set; Telegram; → OrderModel (admin)
│   │   │       ├── ResolveIssueHandler.php       # DELIVERY_ISSUE→DELIVERING|CANCELLED; SMS; → OrderModel
│   │   │       ├── GetOrderByIdHandler.php       # user_id filter; → OrderModel (customer)
│   │   │       ├── GetAnyOrderByIdHandler.php    # no user filter; → OrderModel (manager/admin)
│   │   │       ├── GetMyOrdersHandler.php        # → paginator (customer)
│   │   │       ├── GetPaidOrdersHandler.php      # status=paid; → paginator (manager)
│   │   │       ├── GetAdminOrdersHandler.php     # all orders; → paginator (admin)
│   │   │       ├── GetCourierOrdersHandler.php   # courier_id filter; ready/delivering; → paginator
│   │   │       └── GetCourierOrderByIdHandler.php # courier_id + orderId filter; → OrderModel
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   ├── 2026_06_23_000003_create_orders_table.php
│   │   │       │   └── 2026_06_23_000004_create_order_items_table.php
│   │   │       ├── Models/
│   │   │       │   ├── OrderModel.php            # status→OrderStatus cast, address→array cast
│   │   │       │   └── OrderItemModel.php        # belongsTo Order+Product
│   │   │       └── Repositories/
│   │   │           └── EloquentOrderRepository.php  # toDomain(); create/update split
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── OrderController.php           # index|show|store|cancel|paidOrders|confirm|readyToDeliver|markDelivering|markDelivered|notFound
│   │       ├── Requests/
│   │       │   ├── CreateOrderRequest.php        # items,address,phone,delivery_time,payment_method
│   │       │   ├── AssignCourierRequest.php      # courier_id(exists:users)
│   │       │   ├── ResolveIssueRequest.php       # action(reschedule|cancel)
│   │       │   └── NotFoundRequest.php           # reason(required)
│   │       ├── Resources/
│   │       │   └── OrderResource.php             # status.value, address, items(product_name,subtotal), payment_url(virtual)
│   │       └── routes/
│   │           └── api.php                       # 18 endpoint: Customer(auth:api)|Manager(auth:sanctum,role.manager)|Admin(role.admin)|Courier(role.courier)
│   │
│   ├── Courier/                        # ✅ DDD to'liq — profil, tarix, statistika
│   │   ├── Domain/
│   │   │   ├── ValueObjects/
│   │   │   │   └── CourierStats.php    # readonly: totalDelivered, totalNotFound, totalActive, successRate(auto)
│   │   │   └── Exceptions/
│   │   │       └── CourierNotFoundException.php
│   │   ├── Application/
│   │   │   ├── Queries/
│   │   │   │   ├── GetCourierProfileQuery.php  # courierId
│   │   │   │   ├── GetCourierHistoryQuery.php  # courierId
│   │   │   │   └── GetCourierStatsQuery.php    # courierId
│   │   │   └── Handlers/
│   │   │       ├── GetCourierProfileHandler.php  # Staff::find + role check → Staff model
│   │   │       ├── GetCourierHistoryHandler.php  # delivered orders, paginate(15) → OrderModel paginator
│   │   │       └── GetCourierStatsHandler.php    # count queries → CourierStats VO
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── CourierController.php  # profile|history|stats
│   │       ├── Resources/
│   │       │   ├── CourierProfileResource.php  # id, name, email, role, is_active, created_at
│   │       │   └── CourierStatsResource.php    # total_delivered, total_not_found, total_active, success_rate
│   │       └── routes/
│   │           └── api.php              # GET courier/profile|history|stats (auth:sanctum + role.courier)
│   │
│   ├── Admin/                          # ✅ Staff auth tayyor
│   │   ├── Domain/
│   │   │   └── Enums/
│   │   │       └── StaffRole.php       # manager|courier|admin|super_admin
│   │   ├── Application/
│   │   │   ├── DTOs/
│   │   │   │   └── StaffLoginDTO.php   # email, password; fromRequest()
│   │   │   └── Handlers/
│   │   │       └── StaffLoginHandler.php  # Hash::check; is_active; createToken(role); → [staff, token]
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   └── 2026_06_23_000005_create_staff_table.php
│   │   │       └── Models/
│   │   │           └── Staff.php       # extends Authenticatable, HasApiTokens; role→StaffRole cast
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── StaffAuthController.php  # login(→200+token) | logout(→200)
│   │       ├── Requests/
│   │       │   └── StaffLoginRequest.php
│   │       └── routes/
│   │           └── api.php             # POST staff/login | POST staff/logout (auth:sanctum)
│   │
│   │
│   ├── Review/                         # ✅ DDD to'liq — sharh + moderatsiya
│   │   ├── Domain/
│   │   │   ├── Enums/
│   │   │   │   └── ReviewStatus.php    # PENDING, APPROVED, REJECTED
│   │   │   ├── Entities/
│   │   │   │   └── Review.php          # public private(set): rating,comment,status,isVisible,adminNote; approve/reject/update
│   │   │   ├── Exceptions/
│   │   │   │   ├── ReviewAlreadyExistsException.php  # 422
│   │   │   │   ├── OrderNotDeliveredException.php    # 422
│   │   │   │   └── ReviewNotFoundException.php       # 404
│   │   │   └── Repositories/
│   │   │       └── ReviewRepositoryInterface.php     # findById, findByOrderId, save(int)
│   │   ├── Application/
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateReviewDTO.php  # orderId, userId, productId, rating, comment; fromRequest()
│   │   │   │   └── UpdateReviewDTO.php  # rating, comment; fromRequest()
│   │   │   ├── Commands/
│   │   │   │   ├── CreateReviewCommand.php   # wraps CreateReviewDTO; fromRequest()
│   │   │   │   ├── UpdateReviewCommand.php   # reviewId + userId + dto
│   │   │   │   ├── ApproveReviewCommand.php  # reviewId
│   │   │   │   └── RejectReviewCommand.php   # reviewId, reason
│   │   │   ├── Queries/
│   │   │   │   ├── GetProductReviewsQuery.php  # productId
│   │   │   │   ├── GetPendingReviewsQuery.php  # (admin)
│   │   │   │   └── GetMyReviewsQuery.php       # userId
│   │   │   └── Handlers/
│   │   │       ├── CreateReviewHandler.php     # order ownership + DELIVERED check + duplicate check; → ReviewModel (201)
│   │   │       ├── UpdateReviewHandler.php     # ownership check; review.update() → PENDING; → ReviewModel (200)
│   │   │       ├── ApproveReviewHandler.php    # review.approve(); product rating/reviews_count yangilash; → void
│   │   │       ├── RejectReviewHandler.php     # review.reject(reason); → void
│   │   │       ├── GetProductReviewsHandler.php  # approved+visible; paginate(10)
│   │   │       ├── GetPendingReviewsHandler.php  # status=pending, with(user); paginate(20)
│   │   │       └── GetMyReviewsHandler.php       # user_id filter; barcha statuslar; paginate(15)
│   │   ├── Infrastructure/
│   │   │   └── Persistence/
│   │   │       ├── Migrations/
│   │   │       │   ├── 2026_06_23_000007_create_reviews_table.php  # order_id(unique FK), rating, status, is_visible, admin_note
│   │   │       │   └── 2026_06_23_000008_add_rating_to_products_table.php  # rating(decimal 3,1), reviews_count
│   │   │       ├── Models/
│   │   │       │   └── ReviewModel.php   # casts: status→ReviewStatus, is_visible→bool; belongsTo Order+User+Product
│   │   │       └── Repositories/
│   │   │           └── EloquentReviewRepository.php  # toDomain(); save() returns int
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── ReviewController.php  # productReviews|store|update|myReviews|pendingReviews|approve|reject
│   │       ├── Requests/
│   │       │   ├── CreateReviewRequest.php  # order_id,product_id,rating(1-5),comment(nullable,max:1000)
│   │       │   ├── UpdateReviewRequest.php  # rating,comment
│   │       │   └── RejectReviewRequest.php  # reason(max:500)
│   │       ├── Resources/
│   │       │   └── ReviewResource.php   # user(whenLoaded), admin_note(when user loaded)
│   │       └── routes/
│   │           └── api.php              # GET products/{id}/reviews | POST|PUT|GET reviews(auth:api) | admin/reviews(role.admin)
│   │
│   └── Payment/                        # ✅ DDD to'liq — Payme, Click, Uzum (test mode)
│       ├── Domain/
│       │   ├── Entities/
│       │   │   └── Payment.php         # public private(set): status, transactionId; markAsPaid/Failed/Cancelled
│       │   ├── Enums/
│       │   │   ├── PaymentStatus.php   # PENDING, PAID, FAILED, CANCELLED
│       │   │   └── PaymentProvider.php # PAYME, CLICK, UZUM
│       │   ├── Exceptions/
│       │   │   ├── DuplicateTransactionException.php  # 422
│       │   │   ├── PaymentNotFoundException.php       # 404
│       │   │   ├── InvalidSignatureException.php      # 401
│       │   │   └── InvalidPaymentAmountException.php  # 422
│       │   └── Repositories/
│       │       └── PaymentRepositoryInterface.php     # findByOrderId, findByTransactionId, save
│       ├── Application/
│       │   ├── Commands/
│       │   │   ├── CreatePaymentCommand.php           # orderId, amount(0=auto), provider
│       │   │   └── MarkPaymentPaidCommand.php         # orderId, transactionId, amount, provider
│       │   ├── DTOs/
│       │   │   └── CreatePaymentDTO.php               # fromCommand(), fromOrder()
│       │   └── Handlers/
│       │       ├── CreatePaymentHandler.php           # checks PENDING status; existing payment reuse; URL builder (Payme base64, Click so'm, Uzum); → [payment_id, payment_url]
│       │       └── MarkPaymentPaidHandler.php         # idempotency(txId+PAID); amount check; DB::transaction(lockForUpdate order+products); SMS+Telegram dispatch
│       ├── Infrastructure/
│       │   ├── Persistence/
│       │   │   ├── Migrations/
│       │   │   │   └── 2026_06_23_000006_create_payments_table.php  # order_id(FK), provider, transaction_id(unique), amount(tiyins), status, payload
│       │   │   ├── Models/
│       │   │   │   └── PaymentModel.php               # casts: status→PaymentStatus, provider→PaymentProvider, payload→array; belongsTo OrderModel
│       │   │   └── Repositories/
│       │   │       └── EloquentPaymentRepository.php  # implements PaymentRepositoryInterface; toDomain()
│       │   └── External/
│       │       ├── Payme/
│       │       │   └── PaymeWebhookHandler.php        # JSON-RPC 2.0; Basic Auth(Paycom:PAYME_KEY/TEST_KEY); 6 methods
│       │       ├── Click/
│       │       │   └── ClickWebhookHandler.php        # 2-step(action=0 prepare, action=1 complete); MD5 signature
│       │       └── Uzum/
│       │           └── UzumWebhookHandler.php         # Basic Auth; methods: GetInformation, PerformTransaction, CancelTransaction
│       └── Presentation/
│           ├── Controllers/
│           │   ├── PaymentController.php              # POST /payment/create (auth:api)
│           │   ├── PaymeWebhookController.php         # POST /payment/payme/webhook (no auth — Basic Auth in handler)
│           │   ├── ClickWebhookController.php         # POST /payment/click/webhook
│           │   └── UzumWebhookController.php          # POST /payment/uzum/webhook
│           ├── Requests/
│           │   └── CreatePaymentRequest.php           # order_id(exists), provider(in:payme,click,uzum)
│           ├── Resources/
│           │   └── PaymentResource.php                # id, order_id, provider.value, status.value, amount, created_at
│           └── routes/
│               └── api.php                           # POST payment/create(auth:api) | POST payment/payme|click|uzum/webhook
│
├── bootstrap/
│   ├── app.php                         # glob route + exception mapping + middleware aliases (role.manager/courier/admin/super_admin)
│   └── providers.php
│
├── config/
│   ├── auth.php                        # guards: api(users), sanctum+manager+courier+admin+super_admin(staff) + otp_ttl_seconds
│   ├── cart.php                        # delivery_price ← DELIVERY_PRICE env
│   ├── payment.php                     # test_mode, payme(id/key/test_key/urls), click(service_id/merchant_id/secret_key), uzum(service_id/username/password)
│   └── telegram.php                    # bot_token, manager_chat_id, api_url
│
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   └── 0001_01_01_000002_create_jobs_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php          # → StaffSeeder
│       └── StaffSeeder.php             # truncate+insert: manager@|courier@|admin@|super@uvita.uz (password123)
│
├── routes/
│   ├── api.php                         # Bo'sh placeholder
│   ├── console.php
│   └── web.php
│
├── docker/
└── docker-compose.yml

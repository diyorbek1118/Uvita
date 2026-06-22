backend/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   │   └── ForceJsonResponse.php
│   │   └── Requests/
│   │       └── BaseRequest.php
│   ├── Jobs/
│   │   └── SendSmsJob.php              # ShouldQueue — SmsService::send() async
│   ├── Providers/
│   │   └── AppServiceProvider.php      # loadModuleMigrations() glob + loadLegacyModuleRoutes()
│   │                                   # Bindings: User, OtpAttempt, TokenService, Category
│   └── Shared/
│       ├── Exceptions/
│       │   └── DomainException.php
│       ├── Responses/
│       │   └── ApiResponse.php
│       └── Services/
│           └── SMS/
│               └── SmsService.php      # send(): void — Log::info() mock
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
│   │   │   │   ├── DeleteCategoryHandler.php         # → void (204)
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
│   │       │   ├── CreateCategoryRequest.php  # name req | slug nullable+unique | image url | parent_id
│   │       │   └── UpdateCategoryRequest.php  # + is_active
│   │       ├── Resources/
│   │       │   └── CategoryResource.php       # id,name,slug,image,parentId,isActive
│   │       └── routes/
│   │           └── api.php             # GET categories (public) | POST/PUT/DELETE (TODO: auth:admin)
│   │
│   ├── User/                           # ⚠️ Domain + Infrastructure tayyor
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── User.php
│   │   │   └── Repositories/
│   │   │       └── UserRepositoryInterface.php
│   │   └── Infrastructure/
│   │       └── Persistence/
│   │           ├── Migrations/
│   │           │   └── 0001_01_01_000000_create_users_table.php
│   │           ├── Models/
│   │           │   └── User.php
│   │           └── Repositories/
│   │               └── EloquentUserRepository.php
│   │
│   └── [Kelgusi: Product, Cart, Order, Payment, Review, Courier, Admin/*]
│
├── bootstrap/
│   ├── app.php                         # glob route + exception mapping
│   └── providers.php
│
├── config/
│   └── auth.php                        # 5 guard + otp_ttl_seconds
│
├── database/
│   └── migrations/
│       ├── 0001_01_01_000001_create_cache_table.php
│       └── 0001_01_01_000002_create_jobs_table.php
│
├── routes/
│   ├── api.php                         # Bo'sh placeholder
│   ├── console.php
│   └── web.php
│
├── docker/
└── docker-compose.yml

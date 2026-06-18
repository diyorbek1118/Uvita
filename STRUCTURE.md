backend/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Shared/
│       └── Services/
│           ├── SMS/
│           │   └── SmsService.php
│           ├── Telegram/
│           │   └── TelegramService.php
│           └── Payment/
│               └── PaymentService.php
│
├── Modules/
│   │
│   ├── Auth/
│   │   ├── Controllers/
│   │   │   └── AuthController.php
│   │   ├── DTOs/
│   │   │   ├── SendOtpDTO.php
│   │   │   └── VerifyOtpDTO.php
│   │   ├── Enums/
│   │   │   └── OtpTypeEnum.php
│   │   ├── Requests/
│   │   │   ├── SendOtpRequest.php
│   │   │   └── VerifyOtpRequest.php
│   │   ├── Services/
│   │   │   └── AuthService.php
│   │   └── routes/
│   │       └── api.php
│   │
│   ├── User/
│   │   ├── Controllers/
│   │   │   └── UserController.php
│   │   ├── Models/
│   │   │   └── User.php
│   │   ├── Requests/
│   │   │   └── UpdateProfileRequest.php
│   │   ├── Resources/
│   │   │   └── UserResource.php
│   │   └── routes/
│   │       └── api.php
│   │
│   ├── Product/
│   │   ├── Controllers/
│   │   │   └── ProductController.php
│   │   ├── Models/
│   │   │   └── Product.php
│   │   ├── Resources/
│   │   │   └── ProductResource.php
│   │   ├── Requests/
│   │   │   └── ProductFilterRequest.php
│   │   └── routes/
│   │       └── api.php
│   │
│   ├── Cart/
│   │   ├── Controllers/
│   │   │   └── CartController.php
│   │   ├── Models/
│   │   │   ├── Cart.php
│   │   │   └── CartItem.php
│   │   ├── Resources/
│   │   │   └── CartResource.php
│   │   └── routes/
│   │       └── api.php
│   │
│   └── Order/
│       ├── Controllers/
│       │   └── OrderController.php
│       ├── Models/
│       │   ├── Order.php
│       │   └── OrderItem.php
│       ├── Enums/
│       │   └── OrderStatusEnum.php
│       ├── Requests/
│       │   └── CreateOrderRequest.php
│       ├── Resources/
│       │   └── OrderResource.php
│       └── routes/
│           └── api.php
│
├── database/
│   └── migrations/
│
├── docker/
│   ├── nginx/
│   └── php/
│
├── docker-compose.yml
├── .env
└── ARCHITECTURE.md
└── STRUCTURE.md

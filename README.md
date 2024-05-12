## Beta version
### Layouts field for MoonShine

Field for repeating groups of fields for MoonShine

### Installation

```shell
composer require moonshine/layouts-field
```

### Usage

```php
use MoonShine\Layouts\Fields\Layouts;

Layouts::make('Content')
    ->addLayout('Contact information', 'contacts', [
        Text::make('Name'),
        Email::make('Email'),
    ]),
```

```php
use MoonShine\Layouts\Casts\LayoutsCast;

class Article extends Model
{
    protected $casts = [
        'field' => LayoutsCast::class,
    ];
}
```


# ![Layouts field for MoonShine](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)
## Layouts field for MoonShine - Beta version

## Quick start

### Install

```shell
composer require moonshine/layouts-field
```

### Usage

Field Layouts for MoonShine allows you to easily manage repeating groups of fields. You will be able to add, delete and sort groups consisting of basic fields.
There are some restrictions on the use of fields in the Layouts field. You can use any basic fields except **Json** and **Relationships** fields.

```php
use MoonShine\Layouts\Fields\Layouts;

Layouts::make('Content')
    ->addLayout('Contact information', 'contacts', [
        Text::make('Name'),
        Email::make('Email'),
    ])
     ->addLayout('Banner section', 'banner', [
        Text::make('Title'),
        Image::make('Banner image', 'thumbnail'),
    ]),
```
#### Adding layouts

Layouts can be added using the following method on your Layouts fields:

```php
addLayout(string $title, string $name, iterable $fields, ?int $limit = null)
```
1. The `$title` parameter allows you to specify the name of a group of fields that will be displayed in the form.
2. The `$name` parameter is used to store the chosen layout in the field's value.
3. The `$fields` parameter accepts an array of fields that will be used to populate a group of fields in the form.
4. `$limit` allows you to set the max number of groups in the field.

#### Adding cast

The field stores its values as a single JSON string. To use the Layouts field, you need to add a cast for your model.

```php
use MoonShine\Layouts\Casts\LayoutsCast;

class Article extends Model
{
    protected $casts = [
        'content' => LayoutsCast::class,
    ];
}

Layouts::make('Content', 'content')
    ->addButton(ActionButton::make('New layout')->icon('heroicons.outline.plus')->primary())
```
#### Customizing the button label

You can change the default "Add layout" button's text using the [ActionButton](https://moonshine-laravel.com/docs/resource/actionbutton/action_button?change-moonshine-locale=en#basics) component:

```php
Layouts::make('Content')
    ->addButton(ActionButton::make('New layout')->icon('heroicons.outline.plus')->primary())
```
#### Adding search field
You can add search input in layout list as follows:
```php
Layouts::make('Content')
  ->addLayout('Info section', 'info', [
    ...
  ])
  ...
  ->addLayout('Slider section', 'slider', [
    ...
  ])
  ->searchable()
```

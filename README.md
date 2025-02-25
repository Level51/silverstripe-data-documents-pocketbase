# Pocketbase Adapter for Data Documents

See main repository [Data Documents](https://github.com/Level51/silverstripe-data-documents) for instructions.

## Installation

```
composer require level51/silverstripe-data-documents-pocketbase
```

## Configuration

| Environment Variable   | Description                     | Required | Example               |
|------------------------|---------------------------------|----------|-----------------------|
| POCKETBASE_URL         | URL of your Pocketbase instance | Yes      | http://localhost:8181 |
| POCKETBASE_ADMIN_USER  | Superuser identifier            | Yes      | -                     |
| POCKETBASE_ADMIN_PASS  | Superuser password              | Yes      | -                     |

## Usage
Check out the main repository [Data Documents](https://github.com/Level51/silverstripe-data-documents) for general usage description. 
Also note the following:

### Collection name
Make sure that you pass the collection name when you create the adapter instance using the `getDocumentStore` method in your data model.

```php
public function getDocumentStore(): DataDocumentStore
{
    return PocketbaseAdapter::create('myCollectionName');
}
```

### Document write options
Custom write options can be defined for each model by defining a `getDocumentWriteOptions` method.

```php
public function getDocumentWriteOptions(): array
{
    return [
        'merge' => false // defaults to true
    ];
}
```

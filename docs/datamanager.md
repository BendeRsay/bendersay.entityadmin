# DataManager
Для каждой сущности обязательно должен быть датаменеджер наследуемый от Bitrix\Main\ORM\Data\DataManager.

В каждом датаменеджере есть метод getTableName().
Для отображения названия сущности в человеко-понятном виде необходимо добавить аннотацию Table к методу getTableName() и указать title.

Пример указания названия сущности:
```php
/**
 * Сущность продукт
 */
class ProductTable extends DataManager
{
    /**
     * @Table(title=CRM Продукт)
     *
     * @inheritDoc
     */
    public static function getTableName(): string
    {
        return 'bz_crm_product';
    }
```

Кроме метода getTableName() в каждом датаменеджере есть метод getMap().
При конструировании поля можно указать его название для человеко-понятного отображения с помощью configureTitle(), а также проставить возможность редактирования с помощью configureAutocomplete().
```php
public static function getMap(): array
    {
        return [
            (new IntegerField(Enum::ID->name))
                /** Первичные ключи запрещены к редактированию */
                ->configurePrimary() 
                /** 
                 * autocomplete поля также запрещены к редактированию.
                 * Можно пользоваться этим флагом для запрета редактирования любых полей 
                 */
                ->configureAutocomplete(), 

            (new StringField(Enum::GUID->name))
                ->configureRequired()
                /** С помощью configureTitle можно указать название столбца */
                ->configureTitle(Enum::GUID->getTitle()), 
        ];
    }
```
____
- [<- Настройки модуля](./settings.md)
- [<- Сущности](./entities.md)


- [Документация](./instruction.md)
- [README.md](../README.md)
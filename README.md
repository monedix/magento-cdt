# Creditea Método de pago para Magento 2
Cuando integras Creditea como método de pago, ofrece a tus clientes la posibilidad de obtener una línea de crédito para comprar en tu tienda y dividir sus pagos hasta en 60 quincenas.

## Compatibilidad
✓ Magento 2.3.x, ✓ Magento 2.4.x
<br/>

###### Ejecuta los siguientes comandos en la ruta base de Magento.

### Instalación

```
composer require monedix/magento-cdt

php bin/magento module:enable Creditea_Magento2
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

### Actualización

```
composer update monedix/magento-cdt

php bin/magento module:enable Creditea_Magento2
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

### Eliminación

```
php bin/magento module:disbale Creditea_Magento2
composer remove monedix/magento-cdt
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
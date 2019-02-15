# yii2-revision

##### Install:

`php composer.phar require mirocow/yii2-revision "dev-master"`

##### Config:

```php
    public function behaviors()
    {
        return [
            'revision' => [
                'class' => ModelRevision::class,
                'revisionModelId' => 'ads_id',
                'revisionUserId' => 'user_id',
                'classModel' => \common\models\essence\AdsRevision::class,
                /*'fields' => [
                    'bargain_flag',
                    'packaging_flag',
                    'documents_flag',
                    'certificate_flag',
                    'certificate_issued',
                    'sex_id',
                    'address',
                    'product_name',
                    'description',
                    'price',
                    'weight',
                    'length',
                    'width',
                    'height',
                    'product_model',
                    'product_model_ref',
                    'brand_id',
                    'material_strap_id',
                    'material_housing_id',
                    'product_depend_cat',
                    'connect_type',
                    'type_mechanism_id',
                    'year_buying',
                    'product_cat_id',
                    'shape_id',
                    'color',
                    'diameter_1',
                    'diameter_2',
                    'type_clock_id',
                    'trusted',
                    'color',
                    'carat',
                    'marketing_type',
                    'sale_type',
                    'state_id',
                ],*/
            ],
        ];
    }
```
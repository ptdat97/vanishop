<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Cart/Application/CartService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      'f6f40035517c56b18048352119b4d857' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a7934c0df3c8a568ab2ddff6dbf11b4c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '78e3879ee1d6fe4836155e8c4ded5ada' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'create',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f78754d7b7612b8b4b59bd5fb9920dc2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'view',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5beaf268d07229655b49926cf61ae974' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'addLine',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '6095654d892ef4bf60de90736015c3ed' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'updateLine',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b85d537f546318db928ed5cd58851b47' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'removeLine',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '0acfbfdd5be5b70106452a5fd047efd6' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'merge',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '447ad1fc14b5545f53dd853b5b5c9de3' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'forCustomer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7d590e23ad6c61cb25971f79e2fcc7f7' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'attachToCustomer',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '85025a12b35e0ef86dc1c353e83f6914' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'lockForCheckout',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a2d2c661b8aeed63dd77267bc87e70ec' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'markConverted',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2ed06631523fca6f6f3636b3059470b8' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'mutate',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '3098c011415a19e5b1d356ede6be587e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'guard',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '439bd4fdbec81922bebd71f7247aa70a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'assertQuantity',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '60f4c698846a4134e4bcd2d1c0feaf8e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'find',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '3c9e1768bfcafe2c5f3372424ca9e970' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'mergeLocked',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '510fcffd5dee738763ebb5d5a4c2e289' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'otherLinesQuantity',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '3d437886043a23c725fbc369ee2078cb' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'normalizeOptions',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b870a348ef153170d66c5fdd9031500b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'optionsHash',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '74a594e76d465554a5fc315d9ca5f87a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'openCartOf',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '97740c303b99d222cba5040fc5b3eb02' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'line',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '0d891650e5bba2f701c5b006f22aa6ab' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'assertOpen',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '85f2b2a52d47d01c1258f9e46c8ec101' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'touch',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e318cfcde9b159567afb1dd1c8d6db71' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'build',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a23f26eb9ec78b06e951d363c68d1e79' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'customerId',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '360c305076d6f5779d4d76083670625f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'locale',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '23cc1947e504661c8c17f65811127ebf' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Cart\\Application',
         'uses' => 
        array (
          'app' => 'Illuminate\\Support\\Facades\\App',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
          'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
          'carts' => 'Modules\\Cart\\Contracts\\Carts',
          'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
          'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
          'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
          'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
          'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
          'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
          'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
          'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
          'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
          'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
          'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
          'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'hook' => 'Modules\\Extension\\Facades\\Hook',
          'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
          'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
          'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
          'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
          'actortype' => 'Modules\\Shared\\Context\\ActorType',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Cart\\Application\\CartService',
         'functionName' => 'now',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Cart\\Application',
           'uses' => 
          array (
            'app' => 'Illuminate\\Support\\Facades\\App',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'cartlineoption' => 'Modules\\Cart\\Contracts\\CartLineOption',
            'cartrejected' => 'Modules\\Cart\\Contracts\\CartRejected',
            'carts' => 'Modules\\Cart\\Contracts\\Carts',
            'cartkey' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
            'cartlinedraft' => 'Modules\\Cart\\Contracts\\Data\\CartLineDraft',
            'cartview' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'newcart' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'invalidcartlineoption' => 'Modules\\Cart\\Contracts\\InvalidCartLineOption',
            'cartlimits' => 'Modules\\Cart\\Domain\\CartLimits',
            'cartstatus' => 'Modules\\Cart\\Domain\\CartStatus',
            'cartupdated' => 'Modules\\Cart\\Events\\CartUpdated',
            'cart' => 'Modules\\Cart\\Persistence\\Models\\Cart',
            'cartline' => 'Modules\\Cart\\Persistence\\Models\\CartLine',
            'catalogreader' => 'Modules\\Catalog\\Contracts\\CatalogReader',
            'sellablevariant' => 'Modules\\Catalog\\Contracts\\Data\\SellableVariant',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'hook' => 'Modules\\Extension\\Facades\\Hook',
            'availabilityreader' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
            'pricingcontext' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
            'resolvedprice' => 'Modules\\Pricing\\Contracts\\Data\\ResolvedPrice',
            'priceresolver' => 'Modules\\Pricing\\Contracts\\PriceResolver',
            'actortype' => 'Modules\\Shared\\Context\\ActorType',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Cart\\Application\\CartService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/Users/dat/Ecommerce/vanishop/modules/Cart/Application/CartService.php' => '01b84ec0078bf04d207d048ba3f35805aafee0f08def1fd5bcbbcf840c82db61',
    ),
  ),
));
<?php

use Stubs\Wallet;
use UmlReflector\Introspector;
use UmlReflector\Directives;

class IntrospectorAggregationTest extends \PHPUnit_Framework_TestCase
{
    private $introspector;
    private $directives;


    public function setUp()
    {
        $this->introspector = new Introspector();
        $this->directives = new Directives();
    }

    public function testDisplaysWalletWithMoneyxxxxxx()
    {
        $wallet = new Wallet();
        $this->introspector->visualize($wallet, $this->directives);
        $this->assertEquals('[Wallet]+->[Money],[Wallet]+->[CreditCard],[CreditCard]^-[Visa],[CreditCard]^-[MasterCard]', $this->directives->toString());
    }
}

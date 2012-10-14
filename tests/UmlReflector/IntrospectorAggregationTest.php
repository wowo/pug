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

    public function testDisplaysWalletWithMoney()
    {
        $wallet = new Wallet();
        $this->introspector->visualize($wallet, $this->directives);

        $splittedDirectives = explode(PHP_EOL, $this->directives->toString());
        $message = sprintf('Output directives are: "%s"', $this->directives->toString());

        $this->assertContains('[Wallet]+->[Money]', $splittedDirectives, $message);
        $this->assertContains('[Wallet]+->[CreditCard]', $splittedDirectives, $message);
        $this->assertContains('[CreditCard]^-[Visa]', $splittedDirectives, $message);
        $this->assertContains('[CreditCard]^-[MasterCard]', $splittedDirectives, $message);

        printf($message . PHP_EOL);
    }
}

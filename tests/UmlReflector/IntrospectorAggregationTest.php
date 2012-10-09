<?php

require __DIR__ . '/IntrospectorTest.php';

use Stubs\Wallet;

class IntrospectorAggregationTest extends \IntrospectorTest
{
    public function testDisplaysWalletWithMoney()
    {
        $this->visualize(new Wallet());
        $this->assertResultIs('[Wallet]+->[Money],[Wallet]+->[CreditCard],[CreditCard]^-[Visa],[CreditCard]^-[MasterCard]');
    }
}

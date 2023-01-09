<?php
namespace App\Tests\Entity;
use App\Entity\Product;
class ProductTest extends \PHPUnit\Framework\TestCase
{
    public function testDefault()
    {
        $product = new Product('Pomme', 'food', 1);
                                 $this->assertSame(0.055, $product->computeTVA());
    }
}

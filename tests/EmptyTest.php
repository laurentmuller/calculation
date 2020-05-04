<?php
namespace App\Tests;

use PHPUnit\Framework\TestCase;

class EmptyTest extends TestCase {

    public function testReport() {
        $this->assertSame('a', 'a');
    }
}

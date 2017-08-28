<?php


class GeneratorTest extends \PHPUnit\Framework\TestCase
{

    public function testGetToken()
    {
        $factory = new \Loopy\Mpesa("CHUJZNJK4XYe9QA1YC70oS2kZJeHSHc9", "3kyfdnAOKzMYCIRc", true);
        $factory->getToken();
    }
}
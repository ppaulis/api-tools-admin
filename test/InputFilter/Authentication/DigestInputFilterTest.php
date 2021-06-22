<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Admin\InputFilter\Authentication;

use Laminas\ApiTools\Admin\InputFilter\Authentication\DigestInputFilter;
use Laminas\InputFilter\Factory;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function sort;
use function str_replace;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;
use function var_export;

class DigestInputFilterTest extends TestCase
{
    public function setUp()
    {
        $this->htdigest = sys_get_temp_dir() . '/' . uniqid() . '.htdigest';
        touch($this->htdigest);
    }

    public function tearDown()
    {
        unlink($this->htdigest);
    }

    public function getInputFilter(): DigestInputFilter
    {
        $factory = new Factory();
        return $factory->createInputFilter([
            'type' => DigestInputFilter::class,
        ]);
    }

    /** @psalm-return array<string, array{0: array<string, mixed>}> */
    public function dataProviderIsValid(): array
    {
        return [
            'valid' => [
                [
                    'accept_schemes' => ['digest'],
                    'digest_domains' => 'foo.local',
                    'realm'          => 'My Realm',
                    'htdigest'       => 'tmp/file.htpasswd',
                    'nonce_timeout'  => 3600,
                ],
            ],
        ];
    }

    /**
     * @psalm-return array<string, array{
     *     0: array<string, mixed>,
     *     1: string[]
     * }>
     */
    public function dataProviderIsInvalid(): array
    {
        return [
            'no-data'              => [
                [],
                [
                    'accept_schemes',
                    'digest_domains',
                    'realm',
                    'htdigest',
                    'nonce_timeout',
                ],
            ],
            'nonce-is-not-a-digit' => [
                [
                    'accept_schemes' => ['digest'],
                    'digest_domains' => 'foo.local',
                    'realm'          => 'My Realm',
                    'htdigest'       => '%HTDIGEST%',
                    'nonce_timeout'  => 'foo',
                ],
                [
                    'nonce_timeout',
                ],
            ],
            'invalid-htdigest'     => [
                [
                    'accept_schemes' => ['digest'],
                    'digest_domains' => 'foo.local',
                    'realm'          => 'My Realm',
                    'htdigest'       => '/foo/bar/baz/bat.htpasswd',
                    'nonce_timeout'  => 3600,
                ],
                [
                    'htdigest',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderIsValid
     */
    public function testIsValid(array $data)
    {
        $data['htdigest'] = $this->htdigest;
        $filter           = $this->getInputFilter();
        $filter->setData($data);
        $this->assertTrue($filter->isValid(), var_export($filter->getMessages(), true));
    }

    /**
     * @dataProvider dataProviderIsInvalid
     */
    public function testIsInvalid(array $data, array $expectedMessageKeys)
    {
        if (isset($data['htdigest'])) {
            $data['htdigest'] = str_replace('%HTDIGEST%', $this->htdigest, $data['htdigest']);
        }

        $filter = $this->getInputFilter();
        $filter->setData($data);
        $this->assertFalse($filter->isValid());

        $messages    = $filter->getMessages();
        $messageKeys = array_keys($messages);
        sort($expectedMessageKeys);
        sort($messageKeys);
        $this->assertEquals($expectedMessageKeys, $messageKeys);
    }
}

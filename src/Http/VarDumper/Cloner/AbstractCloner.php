<?php

namespace LaraGram\Http\VarDumper\Cloner;

use LaraGram\Http\VarDumper\Caster\Caster;
use LaraGram\Http\VarDumper\Exceptions\ThrowingCasterException;

abstract class AbstractCloner implements ClonerInterface
{
    public static array $defaultCasters = [
        '__PHP_Incomplete_Class' => ['LaraGram\Http\VarDumper\Caster\Caster', 'castPhpIncompleteClass'],

        'AddressInfo' => ['LaraGram\Http\VarDumper\Caster\AddressInfoCaster', 'castAddressInfo'],
        'Socket' => ['LaraGram\Http\VarDumper\Caster\SocketCaster', 'castSocket'],

        'LaraGram\Http\VarDumper\Caster\CutStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castStub'],
        'LaraGram\Http\VarDumper\Caster\CutArrayStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castCutArray'],
        'LaraGram\Http\VarDumper\Caster\ClassDumpStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castClassDump'],
        'LaraGram\Http\VarDumper\Caster\ConstStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castStub'],
        'LaraGram\Http\VarDumper\Caster\EnumStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castEnum'],
        'LaraGram\Http\VarDumper\Caster\ScalarStub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'castScalar'],

        'Fiber' => ['LaraGram\Http\VarDumper\Caster\FiberCaster', 'castFiber'],

        'Closure' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castClosure'],
        'Generator' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castGenerator'],
        'ReflectionType' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castType'],
        'ReflectionAttribute' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castAttribute'],
        'ReflectionGenerator' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castReflectionGenerator'],
        'ReflectionClass' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castClass'],
        'ReflectionClassConstant' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castClassConstant'],
        'ReflectionFunctionAbstract' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castFunctionAbstract'],
        'ReflectionMethod' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castMethod'],
        'ReflectionParameter' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castParameter'],
        'ReflectionProperty' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castProperty'],
        'ReflectionReference' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castReference'],
        'ReflectionExtension' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castExtension'],
        'ReflectionZendExtension' => ['LaraGram\Http\VarDumper\Caster\ReflectionCaster', 'castZendExtension'],

        'Doctrine\Common\Persistence\ObjectManager' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'Doctrine\Common\Proxy\Proxy' => ['LaraGram\Http\VarDumper\Caster\DoctrineCaster', 'castCommonProxy'],
        'Doctrine\ORM\Proxy\Proxy' => ['LaraGram\Http\VarDumper\Caster\DoctrineCaster', 'castOrmProxy'],
        'Doctrine\ORM\PersistentCollection' => ['LaraGram\Http\VarDumper\Caster\DoctrineCaster', 'castPersistentCollection'],
        'Doctrine\Persistence\ObjectManager' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],

        'DOMException' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castException'],
        'Dom\Exception' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castException'],
        'DOMStringList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMNameList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMImplementation' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castImplementation'],
        'Dom\Implementation' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castImplementation'],
        'DOMImplementationList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMNode' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\Node' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMNameSpaceNode' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMDocument' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDocument'],
        'Dom\XMLDocument' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castXMLDocument'],
        'Dom\HTMLDocument' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castHTMLDocument'],
        'DOMNodeList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\NodeList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMNamedNodeMap' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\DTDNamedNodeMap' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'DOMXPath' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\XPath' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\HTMLCollection' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],
        'Dom\TokenList' => ['LaraGram\Http\VarDumper\Caster\DOMCaster', 'castDom'],

        'XMLReader' => ['LaraGram\Http\VarDumper\Caster\XmlReaderCaster', 'castXmlReader'],

        'ErrorException' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castErrorException'],
        'Exceptions' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castException'],
        'Error' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castError'],
        'Symfony\Bridge\Monolog\Logger' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'LaraGram\Http\DependencyInjection\ContainerInterface' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'LaraGram\Http\EventDispatcher\EventDispatcherInterface' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'LaraGram\Http\HttpClient\AmpHttpClient' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClient'],
        'LaraGram\Http\HttpClient\CurlHttpClient' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClient'],
        'LaraGram\Http\HttpClient\NativeHttpClient' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClient'],
        'LaraGram\Http\HttpClient\Response\AmpResponse' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClientResponse'],
        'LaraGram\Http\HttpClient\Response\AmpResponseV4' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClientResponse'],
        'LaraGram\Http\HttpClient\Response\AmpResponseV5' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClientResponse'],
        'LaraGram\Http\HttpClient\Response\CurlResponse' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClientResponse'],
        'LaraGram\Http\HttpClient\Response\NativeResponse' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castHttpClientResponse'],
        'LaraGram\Http\HttpFoundation\Request' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castRequest'],
        'LaraGram\Http\Uid\Ulid' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castUlid'],
        'LaraGram\Http\Uid\Uuid' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castUuid'],
        'LaraGram\Http\VarExporter\Internal\LazyObjectState' => ['LaraGram\Http\VarDumper\Caster\SymfonyCaster', 'castLazyObjectState'],
        'LaraGram\Http\VarDumper\Exception\ThrowingCasterException' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castThrowingCasterException'],
        'LaraGram\Http\VarDumper\Caster\TraceStub' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castTraceStub'],
        'LaraGram\Http\VarDumper\Caster\FrameStub' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castFrameStub'],
        'LaraGram\Http\VarDumper\Cloner\AbstractCloner' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'LaraGram\Http\ErrorHandler\Exception\FlattenException' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castFlattenException'],
        'LaraGram\Http\ErrorHandler\Exception\SilencedErrorContext' => ['LaraGram\Http\VarDumper\Caster\ExceptionCaster', 'castSilencedErrorContext'],

        'Imagine\Image\ImageInterface' => ['LaraGram\Http\VarDumper\Caster\ImagineCaster', 'castImage'],

        'Ramsey\Uuid\UuidInterface' => ['LaraGram\Http\VarDumper\Caster\UuidCaster', 'castRamseyUuid'],

        'ProxyManager\Proxy\ProxyInterface' => ['LaraGram\Http\VarDumper\Caster\ProxyManagerCaster', 'castProxy'],
        'PHPUnit_Framework_MockObject_MockObject' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'PHPUnit\Framework\MockObject\MockObject' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'PHPUnit\Framework\MockObject\Stub' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'Prophecy\Prophecy\ProphecySubjectInterface' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],
        'Mockery\MockInterface' => ['LaraGram\Http\VarDumper\Caster\StubCaster', 'cutInternals'],

        'PDO' => ['LaraGram\Http\VarDumper\Caster\PdoCaster', 'castPdo'],
        'PDOStatement' => ['LaraGram\Http\VarDumper\Caster\PdoCaster', 'castPdoStatement'],

        'AMQPConnection' => ['LaraGram\Http\VarDumper\Caster\AmqpCaster', 'castConnection'],
        'AMQPChannel' => ['LaraGram\Http\VarDumper\Caster\AmqpCaster', 'castChannel'],
        'AMQPQueue' => ['LaraGram\Http\VarDumper\Caster\AmqpCaster', 'castQueue'],
        'AMQPExchange' => ['LaraGram\Http\VarDumper\Caster\AmqpCaster', 'castExchange'],
        'AMQPEnvelope' => ['LaraGram\Http\VarDumper\Caster\AmqpCaster', 'castEnvelope'],

        'ArrayObject' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castArrayObject'],
        'ArrayIterator' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castArrayIterator'],
        'SplDoublyLinkedList' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castDoublyLinkedList'],
        'SplFileInfo' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castFileInfo'],
        'SplFileObject' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castFileObject'],
        'SplHeap' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castHeap'],
        'SplObjectStorage' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castObjectStorage'],
        'SplPriorityQueue' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castHeap'],
        'OuterIterator' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castOuterIterator'],
        'WeakMap' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castWeakMap'],
        'WeakReference' => ['LaraGram\Http\VarDumper\Caster\SplCaster', 'castWeakReference'],

        'Redis' => ['LaraGram\Http\VarDumper\Caster\RedisCaster', 'castRedis'],
        'Relay\Relay' => ['LaraGram\Http\VarDumper\Caster\RedisCaster', 'castRedis'],
        'RedisArray' => ['LaraGram\Http\VarDumper\Caster\RedisCaster', 'castRedisArray'],
        'RedisCluster' => ['LaraGram\Http\VarDumper\Caster\RedisCaster', 'castRedisCluster'],

        'DateTimeInterface' => ['LaraGram\Http\VarDumper\Caster\DateCaster', 'castDateTime'],
        'DateInterval' => ['LaraGram\Http\VarDumper\Caster\DateCaster', 'castInterval'],
        'DateTimeZone' => ['LaraGram\Http\VarDumper\Caster\DateCaster', 'castTimeZone'],
        'DatePeriod' => ['LaraGram\Http\VarDumper\Caster\DateCaster', 'castPeriod'],

        'GMP' => ['LaraGram\Http\VarDumper\Caster\GmpCaster', 'castGmp'],

        'MessageFormatter' => ['LaraGram\Http\VarDumper\Caster\IntlCaster', 'castMessageFormatter'],
        'NumberFormatter' => ['LaraGram\Http\VarDumper\Caster\IntlCaster', 'castNumberFormatter'],
        'IntlTimeZone' => ['LaraGram\Http\VarDumper\Caster\IntlCaster', 'castIntlTimeZone'],
        'IntlCalendar' => ['LaraGram\Http\VarDumper\Caster\IntlCaster', 'castIntlCalendar'],
        'IntlDateFormatter' => ['LaraGram\Http\VarDumper\Caster\IntlCaster', 'castIntlDateFormatter'],

        'Memcached' => ['LaraGram\Http\VarDumper\Caster\MemcachedCaster', 'castMemcached'],

        'Ds\Collection' => ['LaraGram\Http\VarDumper\Caster\DsCaster', 'castCollection'],
        'Ds\Map' => ['LaraGram\Http\VarDumper\Caster\DsCaster', 'castMap'],
        'Ds\Pair' => ['LaraGram\Http\VarDumper\Caster\DsCaster', 'castPair'],
        'LaraGram\Http\VarDumper\Caster\DsPairStub' => ['LaraGram\Http\VarDumper\Caster\DsCaster', 'castPairStub'],

        'mysqli_driver' => ['LaraGram\Http\VarDumper\Caster\MysqliCaster', 'castMysqliDriver'],

        'CurlHandle' => ['LaraGram\Http\VarDumper\Caster\CurlCaster', 'castCurl'],

        'Dba\Connection' => ['LaraGram\Http\VarDumper\Caster\ResourceCaster', 'castDba'],

        'GdImage' => ['LaraGram\Http\VarDumper\Caster\GdCaster', 'castGd'],

        'SQLite3Result' => ['LaraGram\Http\VarDumper\Caster\SqliteCaster', 'castSqlite3Result'],

        'PgSql\Lob' => ['LaraGram\Http\VarDumper\Caster\PgSqlCaster', 'castLargeObject'],
        'PgSql\Connection' => ['LaraGram\Http\VarDumper\Caster\PgSqlCaster', 'castLink'],
        'PgSql\Result' => ['LaraGram\Http\VarDumper\Caster\PgSqlCaster', 'castResult'],

        ':process' => ['LaraGram\Http\VarDumper\Caster\ResourceCaster', 'castProcess'],
        ':stream' => ['LaraGram\Http\VarDumper\Caster\ResourceCaster', 'castStream'],

        'OpenSSLAsymmetricKey' => ['LaraGram\Http\VarDumper\Caster\OpenSSLCaster', 'castOpensslAsymmetricKey'],
        'OpenSSLCertificateSigningRequest' => ['LaraGram\Http\VarDumper\Caster\OpenSSLCaster', 'castOpensslCsr'],
        'OpenSSLCertificate' => ['LaraGram\Http\VarDumper\Caster\OpenSSLCaster', 'castOpensslX509'],

        ':persistent stream' => ['LaraGram\Http\VarDumper\Caster\ResourceCaster', 'castStream'],
        ':stream-context' => ['LaraGram\Http\VarDumper\Caster\ResourceCaster', 'castStreamContext'],

        'XmlParser' => ['LaraGram\Http\VarDumper\Caster\XmlResourceCaster', 'castXml'],

        'RdKafka' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castRdKafka'],
        'RdKafka\Conf' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castConf'],
        'RdKafka\KafkaConsumer' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castKafkaConsumer'],
        'RdKafka\Metadata\Broker' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castBrokerMetadata'],
        'RdKafka\Metadata\Collection' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castCollectionMetadata'],
        'RdKafka\Metadata\Partition' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castPartitionMetadata'],
        'RdKafka\Metadata\Topic' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castTopicMetadata'],
        'RdKafka\Message' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castMessage'],
        'RdKafka\Topic' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castTopic'],
        'RdKafka\TopicPartition' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castTopicPartition'],
        'RdKafka\TopicConf' => ['LaraGram\Http\VarDumper\Caster\RdKafkaCaster', 'castTopicConf'],

        'FFI\CData' => ['LaraGram\Http\VarDumper\Caster\FFICaster', 'castCTypeOrCData'],
        'FFI\CType' => ['LaraGram\Http\VarDumper\Caster\FFICaster', 'castCTypeOrCData'],
    ];

    protected int $maxItems = 2500;
    protected int $maxString = -1;
    protected int $minDepth = 1;

    /**
     * @var array<string, list<callable>>
     */
    private array $casters = [];

    /**
     * @var callable|null
     */
    private $prevErrorHandler;

    private array $classInfo = [];
    private int $filter = 0;

    /**
     * @param callable[]|null $casters A map of casters
     *
     * @see addCasters
     */
    public function __construct(?array $casters = null)
    {
        $this->addCasters($casters ?? static::$defaultCasters);
    }

    /**
     * Adds casters for resources and objects.
     *
     * Maps resources or object types to a callback.
     * Use types as keys and callable casters as values.
     * Prefix types with `::`,
     * see e.g. self::$defaultCasters.
     *
     * @param array<string, callable> $casters A map of casters
     */
    public function addCasters(array $casters): void
    {
        foreach ($casters as $type => $callback) {
            $this->casters[$type][] = $callback;
        }
    }

    /**
     * Adds default casters for resources and objects.
     *
     * Maps resources or object types to a callback.
     * Use types as keys and callable casters as values.
     * Prefix types with `::`,
     * see e.g. self::$defaultCasters.
     *
     * @param array<string, callable> $casters A map of casters
     */
    public static function addDefaultCasters(array $casters): void
    {
        self::$defaultCasters = [...self::$defaultCasters, ...$casters];
    }

    /**
     * Sets the maximum number of items to clone past the minimum depth in nested structures.
     */
    public function setMaxItems(int $maxItems): void
    {
        $this->maxItems = $maxItems;
    }

    /**
     * Sets the maximum cloned length for strings.
     */
    public function setMaxString(int $maxString): void
    {
        $this->maxString = $maxString;
    }

    /**
     * Sets the minimum tree depth where we are guaranteed to clone all the items.  After this
     * depth is reached, only setMaxItems items will be cloned.
     */
    public function setMinDepth(int $minDepth): void
    {
        $this->minDepth = $minDepth;
    }

    /**
     * Clones a PHP variable.
     *
     * @param int $filter A bit field of Caster::EXCLUDE_* constants
     */
    public function cloneVar(mixed $var, int $filter = 0): Data
    {
        $this->prevErrorHandler = set_error_handler(function ($type, $msg, $file, $line, $context = []) {
            if (\E_RECOVERABLE_ERROR === $type || \E_USER_ERROR === $type) {
                // Cloner never dies
                throw new \ErrorException($msg, 0, $type, $file, $line);
            }

            if ($this->prevErrorHandler) {
                return ($this->prevErrorHandler)($type, $msg, $file, $line, $context);
            }

            return false;
        });
        $this->filter = $filter;

        if ($gc = gc_enabled()) {
            gc_disable();
        }
        try {
            return new Data($this->doClone($var));
        } finally {
            if ($gc) {
                gc_enable();
            }
            restore_error_handler();
            $this->prevErrorHandler = null;
        }
    }

    /**
     * Effectively clones the PHP variable.
     */
    abstract protected function doClone(mixed $var): array;

    /**
     * Casts an object to an array representation.
     *
     * @param bool $isNested True if the object is nested in the dumped structure
     */
    protected function castObject(Stub $stub, bool $isNested): array
    {
        $obj = $stub->value;
        $class = $stub->class;

        if (str_contains($class, "@anonymous\0")) {
            $stub->class = get_debug_type($obj);
        }
        if (isset($this->classInfo[$class])) {
            [$i, $parents, $hasDebugInfo, $fileInfo] = $this->classInfo[$class];
        } else {
            $i = 2;
            $parents = [$class];
            $hasDebugInfo = method_exists($class, '__debugInfo');

            foreach (class_parents($class) as $p) {
                $parents[] = $p;
                ++$i;
            }
            foreach (class_implements($class) as $p) {
                $parents[] = $p;
                ++$i;
            }
            $parents[] = '*';

            $r = new \ReflectionClass($class);
            $fileInfo = $r->isInternal() || $r->isSubclassOf(Stub::class) ? [] : [
                'file' => $r->getFileName(),
                'line' => $r->getStartLine(),
            ];

            $this->classInfo[$class] = [$i, $parents, $hasDebugInfo, $fileInfo];
        }

        $stub->attr += $fileInfo;
        $a = Caster::castObject($obj, $class, $hasDebugInfo, $stub->class);

        try {
            while ($i--) {
                if (!empty($this->casters[$p = $parents[$i]])) {
                    foreach ($this->casters[$p] as $callback) {
                        $a = $callback($obj, $a, $stub, $isNested, $this->filter);
                    }
                }
            }
        } catch (\Exception $e) {
            $a = [(Stub::TYPE_OBJECT === $stub->type ? Caster::PREFIX_VIRTUAL : '').'⚠' => new ThrowingCasterException($e)] + $a;
        }

        return $a;
    }

    /**
     * Casts a resource to an array representation.
     *
     * @param bool $isNested True if the object is nested in the dumped structure
     */
    protected function castResource(Stub $stub, bool $isNested): array
    {
        $a = [];
        $res = $stub->value;
        $type = $stub->class;

        try {
            if (!empty($this->casters[':'.$type])) {
                foreach ($this->casters[':'.$type] as $callback) {
                    $a = $callback($res, $a, $stub, $isNested, $this->filter);
                }
            }
        } catch (\Exception $e) {
            $a = [(Stub::TYPE_OBJECT === $stub->type ? Caster::PREFIX_VIRTUAL : '').'⚠' => new ThrowingCasterException($e)] + $a;
        }

        return $a;
    }
}

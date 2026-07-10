<?php

namespace LaraGram\Http\VarDumper\Caster;

use LaraGram\Http\VarDumper\Cloner\Stub;

class ClassDumpStub extends Stub
{
    public function __construct(string $class)
    {
        $r = new \ReflectionClass($class);

        $this->type = self::TYPE_OBJECT;
        $this->class = $class;

        if ($f = $r->getFileName()) {
            $this->attr['file'] = $f;
            $this->attr['line'] = $r->getStartLine();
        }

        $properties = [];
        foreach ($r->getProperties(\ReflectionProperty::IS_STATIC) as $p) {
            $key = match (true) {
                $p->isPublic() => $p->getName(),
                $p->isProtected() => Caster::PREFIX_PROTECTED.$p->getName(),
                default => sprintf(Caster::PATTERN_PRIVATE, $class, $p->getName()),
            };
            $properties["$key (static)"] = $p->isInitialized() ? $p->getValue() : new UninitializedStub($p);
        }

        $this->value = $properties;
    }
}

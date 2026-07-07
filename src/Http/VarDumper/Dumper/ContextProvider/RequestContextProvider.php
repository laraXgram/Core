<?php

namespace LaraGram\Http\VarDumper\Dumper\ContextProvider;

use LaraGram\Http\RequestStack;
use LaraGram\Http\VarDumper\Caster\ReflectionCaster;
use LaraGram\Http\VarDumper\Cloner\VarCloner;

final class RequestContextProvider implements ContextProviderInterface
{
    private VarCloner $cloner;

    public function __construct(
        private RequestStack $requestStack,
    ) {
        $this->cloner = new VarCloner();
        $this->cloner->setMaxItems(0);
        $this->cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
    }

    public function getContext(): ?array
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        $controller = $request->attributes->get('_controller');

        return [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'controller' => $controller ? $this->cloner->cloneVar($controller) : $controller,
            'identifier' => hash('xxh128', spl_object_id($request).'@'.$_SERVER['REQUEST_TIME_FLOAT']),
        ];
    }
}

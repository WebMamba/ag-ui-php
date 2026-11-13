<?php

declare(strict_types=1);

namespace AgUi\Bundle\Component;

use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Agent\Toolbox\ToolResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * The agent components, as tools: calling one renders the component as an activity.
 * Their definitions are computed once, when the container is compiled.
 *
 * @phpstan-type ComponentTool array{component: string, class: class-string, description: string, forModel: string|null, parameters: array<string, mixed>|null}
 */
final readonly class AgentComponentToolbox implements ToolboxInterface
{
    /** @param array<string, ComponentTool> $components keyed by tool name */
    public function __construct(
        private ComponentActivityFactory $activities,
        private array $components,
        private ?DenormalizerInterface $denormalizer = null,
        private ?PropertyTypeExtractorInterface $types = null,
    ) {
    }

    public function getTools(): array
    {
        $tools = [];
        foreach ($this->components as $name => $component) {
            // @phpstan-ignore argument.type (the schema was built by Symfony AI's own factory at compile time)
            $tools[] = new Tool(new ExecutionReference($component['class']), $name, $component['description'], $component['parameters']);
        }

        return $tools;
    }

    public function has(string $toolName): bool
    {
        return isset($this->components[$toolName]);
    }

    public function execute(ToolCall $toolCall): ToolResult
    {
        $component = $this->components[$toolCall->getName()] ?? throw new \LogicException(\sprintf('"%s" is not an agent component.', $toolCall->getName()));

        $props = [];
        foreach ($toolCall->getArguments() as $name => $value) {
            $type = $this->types?->getType($component['class'], $name);
            $props[$name] = null !== $type ? $this->denormalize($value, $type) : $value;
        }

        return new ToolResult($toolCall, $this->activities->create($component['component'], $props, $component['forModel']));
    }

    /**
     * The model sends JSON; a component's props may be objects, lists of objects or
     * enums — as they would be when a template renders it.
     */
    private function denormalize(mixed $value, Type $type): mixed
    {
        if ($type instanceof NullableType) {
            if (null === $value) {
                return null;
            }
            $type = $type->getWrappedType();
        }

        if ($type instanceof CollectionType && \is_array($value)) {
            $itemType = $type->getCollectionValueType();

            return array_map(fn (mixed $item): mixed => $this->denormalize($item, $itemType), $value);
        }

        if ($type instanceof ObjectType) {
            $class = $type->getClassName();

            if (is_subclass_of($class, \BackedEnum::class) && (\is_int($value) || \is_string($value))) {
                return $class::tryFrom($value) ?? $value;
            }

            if (\is_array($value) && null !== $this->denormalizer) {
                return $this->denormalizer->denormalize($value, $class);
            }
        }

        return $value;
    }
}

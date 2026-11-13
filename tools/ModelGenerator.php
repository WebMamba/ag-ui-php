<?php

declare(strict_types=1);

namespace AgUi\Tools;

/**
 * Generates the core's model classes from the AG-UI JSON Schema: one final class per
 * object type, one backed enum per closed string set, and one interface plus one
 * dispatcher per discriminated union.
 *
 * The schema uses a small subset of JSON Schema (type, $ref, const, enum, allOf for
 * composition, oneOf discriminated by a const property, required, minimum/maximum,
 * minItems, pattern, not-null), and this generator handles exactly that subset: an
 * unknown construct fails loudly rather than being silently mistranslated.
 */
final class ModelGenerator
{
    private const string ROOT_NAMESPACE = 'AgUi';

    /** Definitions that are only ever composed into or inlined in others. */
    private const array INLINED = ['BaseEvent', 'Attributable', 'BaseMessage', 'Metadata', 'State', 'SubagentRunId', 'JsonPatch', 'JsonPointer'];

    /** Closed string sets declared inline, and the enum they become. */
    private const array INLINE_ENUMS = ['ResumeEntry.status' => 'ResumeStatus'];

    /** @var array<string, array<string, mixed>> */
    private array $defs;

    /** @var array<string, string> generated file path => contents */
    private array $files = [];

    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(array $schema)
    {
        $this->defs = $schema['$defs'];
    }

    /**
     * @return array<string, string> path relative to the core's src/ => contents
     */
    public function generate(): array
    {
        foreach ($this->defs as $name => $def) {
            if (\in_array($name, self::INLINED, true)) {
                continue;
            }

            match (true) {
                isset($def['enum']) => $this->enum($name, $def['enum'], $def['description'] ?? ''),
                isset($def['oneOf']) => $this->union($name, $def),
                isset($def['properties']) || isset($def['allOf']) => $this->model($name, $def),
                default => throw new \LogicException(\sprintf('Do not know how to generate "%s".', $name)),
            };
        }

        foreach (self::INLINE_ENUMS as $location => $enum) {
            [$owner, $property] = explode('.', $location);
            $this->enum($enum, $this->defs[$owner]['properties'][$property]['enum'], $this->defs[$owner]['properties'][$property]['description'] ?? '');
        }

        ksort($this->files);

        return $this->files;
    }

    private function namespaceOf(string $name): string
    {
        return self::ROOT_NAMESPACE.'\\'.match (true) {
            str_ends_with($name, 'Event'), \in_array($name, ['EventType', 'ReasoningEncryptedValueSubtype'], true) => 'Event',
            str_ends_with($name, 'Message'), \in_array($name, ['ToolCall', 'FunctionCall', 'Role', 'TextMessageRole'], true) => 'Message',
            str_ends_with($name, 'Part'), str_ends_with($name, 'Source') => 'Content',
            str_ends_with($name, 'Operation') => 'Patch',
            str_ends_with($name, 'Capabilities'), 'SubagentInfo' === $name => 'Capabilities',
            default => 'Run',
        };
    }

    private function fqcn(string $name): string
    {
        return '\\'.$this->namespaceOf($name).'\\'.$name;
    }

    /** The class that decodes a union: Event → Events, Message → Messages… */
    private function dispatcherOf(string $union): string
    {
        return $union.'s';
    }

    private function write(string $name, string $code, ?string $namespace = null): void
    {
        $namespace ??= $this->namespaceOf($name);
        $path = str_replace('\\', '/', substr($namespace, \strlen(self::ROOT_NAMESPACE) + 1)).'/'.$name.'.php';
        $this->files[$path] = $code;
    }

    // ── Enums ───────────────────────────────────────────────────────────────

    /**
     * @param list<string> $values
     */
    private function enum(string $name, array $values, string $description): void
    {
        $cases = '';
        foreach ($values as $value) {
            $cases .= \sprintf("    case %s = '%s';\n", self::caseName($value), $value);
        }

        $this->write($name, $this->header($this->namespaceOf($name), [])
            .$this->docblock($description, '')
            ."enum {$name}: string\n{\n{$cases}}\n");
    }

    private static function caseName(string $value): string
    {
        return implode('', array_map(static fn (string $part) => ucfirst(strtolower($part)), preg_split('/[_\-\s]+/', $value)));
    }

    // ── Unions ──────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $def
     */
    private function union(string $name, array $def): void
    {
        $members = array_map(fn (array $ref) => $this->refName($ref), $def['oneOf']);
        $discriminator = $this->discriminatorOf($members);
        $namespace = $this->namespaceOf($name);

        // The interface every member implements.
        $methods = match ($name) {
            'Event' => "    /** When the event was created, in milliseconds since the Unix epoch, if known. */\n    public ?int \$timestamp { get; }\n\n"
                ."    /** Which event this is: the value of its \"type\" property. */\n    public function type(): EventType;\n\n"
                ."    /** A copy of this event with its creation time set, in milliseconds since the Unix epoch. */\n    public function withTimestamp(int \$timestamp): static;\n",
            'Message' => "    /** Identifies the message. */\n    public string \$id { get; }\n\n"
                ."    /** Who the message is from: the value of its \"role\" property. */\n    public function role(): Role;\n",
            default => '',
        };
        $parents = 'Message' === $name ? 'Model, Attributable' : 'Model';
        $this->write($name, $this->header($namespace, 'Message' === $name ? ['AgUi\Model\Attributable', 'AgUi\Model\Model'] : ['AgUi\Model\Model'])
            .$this->docblock($def['description'] ?? '', '')
            ."interface {$name} extends {$parents}\n{\n{$methods}}\n");

        // The dispatcher that decodes one, by its discriminator.
        $map = '';
        foreach ($members as $member) {
            $map .= \sprintf("        '%s' => %s::class,\n", $this->constOf($member, $discriminator), $this->fqcn($member));
        }

        $dispatcher = $this->dispatcherOf($name);
        $this->write($dispatcher, $this->header($namespace, ['AgUi\Exception\InvalidShapeException', 'AgUi\Model\DecodeContext', 'AgUi\Model\Json'])
            .$this->docblock(\sprintf('Decodes any %s, telling its kinds apart by "%s".', $name, $discriminator), '')
            ."final class {$dispatcher}\n{\n"
            ."    /** @var array<string, class-string<{$name}>> */\n"
            ."    public const array CLASSES = [\n{$map}    ];\n\n"
            ."    /**\n     * @throws InvalidShapeException\n     */\n"
            ."    public static function fromArray(mixed \$data, DecodeContext \$context = new DecodeContext(), string \$path = ''): {$name}\n    {\n"
            ."        [\$properties, \$kind] = Json::discriminate(\$data, '{$discriminator}', \$path);\n\n"
            ."        \$class = self::CLASSES[\$kind] ?? throw new InvalidShapeException(\$path, 'oneOf', \\sprintf('\"%s\" is not a known {$discriminator}.', \$kind));\n\n"
            ."        return \$class::fromArray(\$properties, \$context, \$path);\n    }\n\n"
            ."    /**\n     * Like fromArray(), except that a kind this SDK does not know, from a newer peer, is\n"
            ."     * dropped and recorded rather than rejected, when the context is lenient: what the\n"
            ."     * protocol asks of a consumer for a member of a list or an optional property.\n     *\n"
            ."     * @throws InvalidShapeException\n     */\n"
            ."    public static function tryFromArray(mixed \$data, DecodeContext \$context = new DecodeContext(), string \$path = ''): ?{$name}\n    {\n"
            ."        [\$properties, \$kind] = Json::discriminate(\$data, '{$discriminator}', \$path);\n\n"
            ."        if (!isset(self::CLASSES[\$kind]) && !\$context->strict) {\n"
            ."            \$context->drop(\$path);\n\n"
            ."            return null;\n        }\n\n"
            ."        return self::fromArray(\$properties, \$context, \$path);\n    }\n}\n", $namespace);
    }

    /**
     * @param list<string> $members
     */
    private function discriminatorOf(array $members): string
    {
        foreach (['type', 'role', 'op'] as $candidate) {
            if (array_all($members, fn (string $m) => null !== $this->constOf($m, $candidate, false))) {
                return $candidate;
            }
        }

        throw new \LogicException(\sprintf('No discriminator for %s.', implode(', ', $members)));
    }

    private function constOf(string $member, string $property, bool $throw = true): ?string
    {
        $const = $this->properties($member)[0][$property]['const'] ?? null;

        if (null === $const && $throw) {
            throw new \LogicException(\sprintf('"%s" has no const "%s".', $member, $property));
        }

        return $const;
    }

    // ── Models ──────────────────────────────────────────────────────────────

    /**
     * Own and composed properties, own first, with the merged required list.
     *
     * @return array{array<string, array<string, mixed>>, list<string>, list<string>} properties, required, own property names
     */
    private function properties(string $name): array
    {
        $def = $this->defs[$name];
        $own = $def['properties'] ?? [];
        $properties = $own;
        $required = $def['required'] ?? [];

        foreach ($def['allOf'] ?? [] as $part) {
            [$inherited, $inheritedRequired] = $this->properties($this->refName($part));
            foreach ($inherited as $property => $schema) {
                $properties[$property] ??= $schema;
            }
            $required = array_values(array_unique([...$required, ...$inheritedRequired]));
        }

        return [$properties, $required, array_keys($own)];
    }

    private function composes(string $name, string $part): bool
    {
        foreach ($this->defs[$name]['allOf'] ?? [] as $ref) {
            if ($part === $this->refName($ref) || $this->composes($this->refName($ref), $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $def
     */
    private function model(string $name, array $def): void
    {
        [$schemas, $required] = $this->properties($name);
        $unions = $this->unionsOf($name);

        $implicit = [];   // required consts: implied by the class, checked on decode
        $properties = []; // everything else becomes a constructor parameter
        foreach ($schemas as $property => $schema) {
            if (isset($schema['const']) && \in_array($property, $required, true)) {
                $implicit[$property] = $schema['const'];
                continue;
            }

            $properties[$property] = [
                'type' => $this->resolve($schema, $name.'.'.$property),
                'required' => \in_array($property, $required, true),
                'description' => $schema['description'] ?? '',
            ];
        }

        // An open object (no unevaluatedProperties: false) keeps the members it does not
        // declare, verbatim: RFC 6902 lets a patch operation carry them.
        $open = false !== ($def['unevaluatedProperties'] ?? null) && false !== ($def['additionalProperties'] ?? null);

        // Required parameters first, then optional ones, each in schema order.
        uksort($properties, static fn (string $a, string $b) => [!$properties[$a]['required']] <=> [!$properties[$b]['required']]);

        $uses = ['AgUi\Model\DecodeContext', 'AgUi\Model\Json'];
        $implements = [];
        foreach ($unions as $union) {
            $implements[] = $union;
            if ($this->namespaceOf($union) !== $this->namespaceOf($name)) {
                $uses[] = ltrim($this->fqcn($union), '\\');
            }
        }
        if ([] === $unions) {
            $implements[] = 'Model';
            $uses[] = 'AgUi\Model\Model';
        }
        if ($this->composes($name, 'Attributable')) {
            $implements[] = 'Attributable';
            $uses[] = 'AgUi\Model\Attributable';
        }
        if (array_any($properties, fn (array $p) => [] !== $this->constraints($p['type']))) {
            $uses[] = 'Symfony\Component\Validator\Constraints as Assert';
        }

        $code = $this->header($this->namespaceOf($name), $uses)
            .$this->docblock($def['description'] ?? '', '')
            ."final class {$name} implements ".implode(', ', $implements)."\n{\n"
            .$this->constructor($properties, $open)
            .$this->unionMethods($name, $unions, $implicit)
            .$this->fromArray($name, $properties, $implicit, $open)
            .$this->toArray($properties, $implicit, $open)
            ."}\n";

        $this->write($name, $code);
    }

    /**
     * @return list<string>
     */
    private function unionsOf(string $name): array
    {
        $unions = [];
        foreach ($this->defs as $union => $def) {
            if (isset($def['oneOf']) && \in_array($name, array_map(fn ($r) => $this->refName($r), array_filter($def['oneOf'], static fn ($r) => isset($r['$ref']))), true)) {
                $unions[] = $union;
            }
        }

        return $unions;
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     */
    private function constructor(array $properties, bool $open): string
    {
        $doc = [];
        $params = '';
        foreach ($properties as $property => $p) {
            $t = $p['type'];
            $doc[] = \sprintf('@param %s $%s %s', $t['doc'].($p['required'] ? '' : ('mixed' === $t['doc'] ? '' : '|null')), $property, self::oneLine($p['description']));

            foreach ($this->constraints($t) as $constraint) {
                $params .= "        {$constraint}\n";
            }
            $declared = $p['required'] || 'mixed' === $t['php'] ? $t['php'] : ('?'.$t['php']);
            if (!$p['required'] && str_contains($t['php'], '|')) {
                $declared = $t['php'].'|null';
            }
            $params .= \sprintf("        public private(set) %s \$%s%s,\n", $declared, $property, $p['required'] ? '' : ' = null');
        }

        if ($open) {
            $doc[] = '@param array<array-key, mixed> $extra Members the protocol does not define, carried verbatim.';
            $params .= "        public private(set) array \$extra = [],\n";
        }

        if ('' === $params) {
            return "    public function __construct()\n    {\n    }\n";
        }

        return $this->docblock(implode("\n", $doc), '    ', wrapTags: true)
            ."    public function __construct(\n{$params}    ) {\n    }\n";
    }

    /**
     * @param list<string> $unions
     * @param array<string, string> $implicit
     */
    private function unionMethods(string $name, array $unions, array $implicit): string
    {
        $code = '';

        if (\in_array('Event', $unions, true)) {
            $case = self::caseName($implicit['type']);
            $code .= "\n    public function type(): EventType\n    {\n        return EventType::{$case};\n    }\n"
                ."\n    public function withTimestamp(int \$timestamp): static\n    {\n        \$event = clone \$this;\n        \$event->timestamp = \$timestamp;\n\n        return \$event;\n    }\n";
        }

        if (\in_array('Message', $unions, true)) {
            $case = self::caseName($implicit['role']);
            $code .= "\n    public function role(): Role\n    {\n        return Role::{$case};\n    }\n";
        }

        return $code;
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     * @param array<string, string> $implicit
     */
    private function fromArray(string $name, array $properties, array $implicit, bool $open): string
    {
        $known = implode(', ', array_map(static fn ($p) => "'{$p}'", [...array_keys($implicit), ...array_keys($properties)]));

        $code = "\n    public static function fromArray(array|\\stdClass \$data, DecodeContext \$context = new DecodeContext(), string \$path = ''): static\n    {\n"
            .($open
                ? "        \$properties = Json::object(\$data, \$path);\n"
                : "        \$properties = Json::closed(Json::object(\$data, \$path), [{$known}], \$context, \$path);\n");

        foreach ($implicit as $property => $value) {
            $code .= "        Json::const(Json::required(\$properties, '{$property}', \$path), '{$value}', Json::pointer(\$path, '{$property}'));\n";
        }

        if ([] === $properties) {
            return $code."\n        return new self();\n    }\n";
        }

        $code .= "\n        return new self(\n";
        foreach ($properties as $property => $p) {
            $at = "Json::pointer(\$path, '{$property}')";
            if ($p['required']) {
                $value = $this->decode($p['type'], "Json::required(\$properties, '{$property}', \$path)", $at);
            } else {
                // An optional property set to null is absent to a lenient reader, an error to a strict one.
                $value = "\\array_key_exists('{$property}', \$properties) && Json::present(\$properties['{$property}'], \$context, {$at}) ? ".$this->decode($p['type'], "\$properties['{$property}']", $at, optional: true).' : null';
            }
            $code .= "            {$property}: {$value},\n";
        }
        if ($open) {
            $code .= "            extra: array_map(Json::value(...), array_diff_key(\$properties, array_flip([{$known}]))),\n";
        }

        return $code."        );\n    }\n";
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     * @param array<string, string> $implicit
     */
    private function toArray(array $properties, array $implicit, bool $open): string
    {
        $entries = '';
        foreach ($implicit as $property => $value) {
            $entries .= "            '{$property}' => '{$value}',\n";
        }
        $optional = '';
        foreach ($properties as $property => $p) {
            $value = $this->encode($p['type'], "\$this->{$property}");
            if ($p['required']) {
                $entries .= "            '{$property}' => {$value},\n";
            } else {
                $optional .= "\n        if (null !== \$this->{$property}) {\n            \$data['{$property}'] = {$value};\n        }\n";
            }
        }

        $assign = '' === $entries ? "        \$data = [];\n" : "        \$data = [\n{$entries}        ];\n";
        $return = $open ? '$data + $this->extra' : '$data';

        return "\n    public function toArray(): array\n    {\n{$assign}{$optional}\n        return {$return};\n    }\n";
    }

    // ── Types ───────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed> kind, php (declared type), doc (phpdoc type), and per-kind details
     */
    private function resolve(array $schema, string $location): array
    {
        if (isset($schema['$ref'])) {
            $ref = $this->refName($schema);

            return match ($ref) {
                'Metadata' => ['kind' => 'object', 'php' => 'array', 'doc' => 'array<array-key, mixed>'],
                'State' => ['kind' => 'any', 'php' => 'mixed', 'doc' => 'mixed', 'nullable' => !isset($schema['not'])],
                'SubagentRunId' => ['kind' => 'string', 'php' => 'string', 'doc' => 'string'],
                'JsonPointer' => ['kind' => 'string', 'php' => 'string', 'doc' => 'string', 'pattern' => $this->defs['JsonPointer']['pattern']],
                'JsonPatch' => $this->listOf(['$ref' => '#/$defs/JsonPatchOperation'], $location, []),
                default => match (true) {
                    isset($this->defs[$ref]['enum']) => ['kind' => 'enum', 'class' => $ref, 'php' => $this->fqcn($ref), 'doc' => $this->fqcn($ref)],
                    isset($this->defs[$ref]['oneOf']) => ['kind' => 'union', 'class' => $ref, 'php' => $this->fqcn($ref), 'doc' => $this->fqcn($ref)],
                    default => ['kind' => 'model', 'class' => $ref, 'php' => $this->fqcn($ref), 'doc' => $this->fqcn($ref)],
                },
            };
        }

        if (isset($schema['const'])) {
            return ['kind' => 'const', 'value' => $schema['const'], 'php' => 'string', 'doc' => "'{$schema['const']}'"];
        }

        if (isset($schema['enum'])) {
            $enum = self::INLINE_ENUMS[$location] ?? throw new \LogicException(\sprintf('No enum name for %s.', $location));

            return ['kind' => 'enum', 'class' => $enum, 'php' => $this->fqcn($enum), 'doc' => $this->fqcn($enum)];
        }

        if (isset($schema['oneOf'])) {
            // The one inline oneOf: content that is either text or a list of parts.
            $types = array_map(static fn (array $s) => $s['type'] ?? null, $schema['oneOf']);
            if (['string', 'array'] !== $types) {
                throw new \LogicException(\sprintf('Unsupported inline oneOf at %s.', $location));
            }
            $list = $this->listOf($schema['oneOf'][1]['items'], $location, $schema['oneOf'][1]);

            return ['kind' => 'stringOrList', 'list' => $list, 'php' => 'string|array', 'doc' => 'string|'.$list['doc']];
        }

        return match ($schema['type'] ?? null) {
            'string' => ['kind' => 'string', 'php' => 'string', 'doc' => 'string'],
            'boolean' => ['kind' => 'bool', 'php' => 'bool', 'doc' => 'bool'],
            'integer' => ['kind' => 'int', 'php' => 'int', 'doc' => 'int', 'minimum' => $schema['minimum'] ?? null, 'maximum' => $schema['maximum'] ?? null],
            'object' => ['kind' => 'object', 'php' => 'array', 'doc' => 'array<array-key, mixed>'],
            'array' => $this->listOf($schema['items'], $location, $schema),
            // No type at all: any JSON value. "not null" only forbids writing null.
            null => ['kind' => 'any', 'php' => 'mixed', 'doc' => 'mixed', 'nullable' => !isset($schema['not'])],
            default => throw new \LogicException(\sprintf('Unsupported type at %s.', $location)),
        };
    }

    /**
     * @param array<string, mixed> $items
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function listOf(array $items, string $location, array $schema): array
    {
        $item = $this->resolve($items, $location.'[]');

        return ['kind' => 'list', 'item' => $item, 'php' => 'array', 'doc' => 'list<'.$item['doc'].'>', 'minItems' => $schema['minItems'] ?? null];
    }

    /**
     * Validator constraints for what PHP's type system cannot say.
     *
     * @param array<string, mixed> $t
     *
     * @return list<string>
     */
    private function constraints(array $t): array
    {
        $constraints = [];

        if ('int' === $t['kind'] && (null !== $t['minimum'] || null !== $t['maximum'])) {
            $constraints[] = \sprintf('#[Assert\Range(min: %s, max: %s)]', var_export($t['minimum'], true), var_export($t['maximum'], true));
        }
        if (isset($t['pattern'])) {
            $constraints[] = \sprintf("#[Assert\\Regex(pattern: '#%s#')]", $t['pattern']);
        }
        if ('list' === $t['kind']) {
            if (null !== $t['minItems']) {
                $constraints[] = \sprintf('#[Assert\Count(min: %d)]', $t['minItems']);
            }
            if (isset($t['item']['pattern'])) {
                $constraints[] = \sprintf("#[Assert\\All([new Assert\\Regex(pattern: '#%s#')])]", $t['item']['pattern']);
            }
        }
        if (\in_array($t['kind'], ['model', 'union'], true)
            || ('list' === $t['kind'] && \in_array($t['item']['kind'], ['model', 'union'], true))
        ) {
            $constraints[] = '#[Assert\Valid]';
        }

        return $constraints;
    }

    /**
     * @param array<string, mixed> $t
     */
    private function decode(array $t, string $value, string $at, bool $optional = false): string
    {
        // Present-but-null is only legal for values that may be null; an optional
        // property with no value is left out, never written as null.
        $nullable = 'any' === $t['kind'] && $t['nullable'];
        if (!$nullable && 'any' === $t['kind']) {
            $value = "Json::forbidNull({$value}, {$at})";
        }

        return match ($t['kind']) {
            'string' => "Json::string({$value}, {$at})",
            'int' => "Json::int({$value}, {$at})",
            'bool' => "Json::bool({$value}, {$at})",
            'const' => "Json::const({$value}, '{$t['value']}', {$at})",
            'any' => "Json::value({$value})",
            'object' => "Json::openObject({$value}, {$at})",
            'enum' => "Json::enum({$value}, {$t['php']}::class, {$at})",
            'model' => "{$t['php']}::fromArray(Json::object({$value}, {$at}), \$context, {$at})",
            'union' => $optional
                ? "{$this->unionDispatcher($t)}::tryFromArray({$value}, \$context, {$at})"
                : "{$this->unionDispatcher($t)}::fromArray({$value}, \$context, {$at})",
            'list' => 'union' === $t['item']['kind']
                ? "array_values(array_filter(Json::list({$value}, {$at}, static fn (mixed \$item, string \$at) => {$this->unionDispatcher($t['item'])}::tryFromArray(\$item, \$context, \$at))))"
                : "Json::list({$value}, {$at}, static fn (mixed \$item, string \$at) => ".$this->decode($t['item'], '$item', '$at').')',
            'stringOrList' => "Json::stringOrList({$value}, {$at}, static fn (mixed \$item, string \$at) => {$this->unionDispatcher($t['list']['item'])}::tryFromArray(\$item, \$context, \$at))",
        };
    }

    /**
     * @param array<string, mixed> $t
     */
    private function unionDispatcher(array $t): string
    {
        return '\\'.$this->namespaceOf($t['class']).'\\'.$this->dispatcherOf($t['class']);
    }

    /**
     * @param array<string, mixed> $t
     */
    private function encode(array $t, string $value): string
    {
        return match ($t['kind']) {
            'string', 'int', 'bool', 'const', 'any' => $value,
            'object' => "Json::encodeObject({$value})",
            'enum' => "{$value}->value",
            'model', 'union' => "Json::encodeObject({$value}->toArray())",
            'list' => \in_array($t['item']['kind'], ['model', 'union', 'enum', 'object'], true)
                ? "array_map(static fn ({$t['item']['php']} \$item) => ".$this->encode($t['item'], '$item').", {$value})"
                : $value,
            'stringOrList' => "\\is_string({$value}) ? {$value} : array_map(static fn ({$t['list']['item']['php']} \$item) => ".$this->encode($t['list']['item'], '$item').", {$value})",
        };
    }

    // ── Rendering helpers ───────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $ref
     */
    private function refName(array $ref): string
    {
        return substr($ref['$ref'], \strlen('#/$defs/'));
    }

    /**
     * @param list<string> $uses
     */
    private function header(string $namespace, array $uses): string
    {
        sort($uses);
        $use = '' === implode('', $uses) ? '' : implode('', array_map(static fn (string $u) => "use {$u};\n", array_unique($uses)))."\n";

        return "<?php\n\n"
            ."// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.\n\n"
            ."declare(strict_types=1);\n\n"
            ."namespace {$namespace};\n\n"
            .$use;
    }

    private function docblock(string $text, string $indent, bool $wrapTags = false): string
    {
        $text = trim(str_replace('*/', '*\/', $text));
        if ('' === $text) {
            return '';
        }

        $lines = [];
        foreach (explode("\n", $text) as $paragraph) {
            // A tag stays on one line: php-cs-fixer would realign a wrapped one into a column.
            if ($wrapTags && str_starts_with($paragraph, '@')) {
                $lines[] = $paragraph;
                continue;
            }
            foreach (explode("\n", wordwrap($paragraph, 92 - \strlen($indent))) as $line) {
                $lines[] = $line;
            }
        }

        return "{$indent}/**\n".implode('', array_map(static fn (string $l) => rtrim("{$indent} * {$l}")."\n", $lines))."{$indent} */\n";
    }

    private static function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}

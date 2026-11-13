<?php

declare(strict_types=1);

namespace AgUi;

/**
 * The AG-UI protocol version this SDK implements.
 */
final class Protocol
{
    /**
     * The version segment of the JSON Schema's $id (https://ag-ui.com/spec/1.0/schema.json),
     * as producers declare it in RUN_STARTED.protocolVersion.
     */
    public const string VERSION = '1.0';

    /** The media type of an AG-UI event stream over Server-Sent Events. */
    public const string SSE_MEDIA_TYPE = 'text/event-stream';
}

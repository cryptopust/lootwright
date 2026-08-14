<?php

namespace Lootwright\Domain\PolicyProvenance;

enum AccessMode: string
{
    case LocalUpload = 'local_upload';
    case PastedText = 'pasted_text';
    case AuthenticatedApi = 'authenticated_api';
    case AnonymousHttp = 'anonymous_http';
    case RemoteFetch = 'remote_fetch';
}

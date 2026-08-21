<?php

namespace Lootwright\Domain\BuildIntake\Import;

enum BuildInputType: string
{
    case PobShareCode = 'pob_share_code';
    case DecodedXml = 'decoded_xml';
    case ItemText = 'item_text';
}

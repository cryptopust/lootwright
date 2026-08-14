<?php

namespace Lootwright\Domain\PolicyProvenance;

enum SourceType: string
{
    case FirstPartyOriginal = 'first_party_original';
    case UserSupplied = 'user_supplied';
    case OfficialDocumentedApi = 'official_documented_api';
    case OpenSourceProject = 'open_source_project';
    case CommunityDataset = 'community_dataset';
    case ThirdPartySite = 'third_party_site';
}

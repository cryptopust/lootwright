<?php

return [
    // Exposure is independently gate-controlled per edition; runtime still
    // fails closed when an exact immutable ruleset is unavailable.
    // PoE1 is the released public scope. PoE2 remains independently
    // gate-controlled and cannot be exposed merely because its adapter exists.
    'public' => ['poe1'],
];

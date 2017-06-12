<?php

namespace degordian\geofencing\enums;

class GeoIpFilterMode
{
    /**
     * There are two modes of working.
     * FILTER_ALLOW will allow only isoCodes that are specified in $isoCodes
     * FILTER_DENY will allow all except the isoCodes that are specified in $isoCodes
     */
    const ALLOW = 'allow';
    const DENY = 'deny';
}
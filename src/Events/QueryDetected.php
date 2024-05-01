<?php

namespace BlissJaspis\QueryDetector\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class QueryDetected {
    use SerializesModels;

    public function __construct(
        public Collection $queries
    ){}

    public function getQueries() : Collection
    {
        return $this->queries;
    }
}

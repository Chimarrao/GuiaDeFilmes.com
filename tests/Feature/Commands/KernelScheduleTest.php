<?php

it('registra o schedule sem erro (cache, justwatch, trailers, refresh, sitemap)', function () {
    $this->artisan('schedule:list')->assertExitCode(0);
});

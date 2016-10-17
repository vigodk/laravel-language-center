<?php

namespace Novasa\LaravelLanguageCenter;

use Illuminate\Console\Command;

class CacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'languagecenter:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache data from langaugecenter.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $platforms = ['web'];

        $translator = app('translator');

        $translator->updateLanguages(false);

        $languages = $translator->getLanguages();

        foreach ($languages as $language) {
            foreach ($platforms as $platform) {
                $translator->updateStrings($language, $platform, false);
            }
        }

        $this->info('Cache update completed!');
    }
}

<?php
namespace Znck\States;

use DB;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class UpdateStatesCommand extends Command
{
    const QUERY_LIMIT = 100;
    const INSTALL_HISTORY = 'vendor/znck/states/install.txt';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'states:update {--f|force : Force update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update/Install states in database.';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var FileLoader
     */
    protected $loader;

    /**
     * @var string
     */
    protected $countries;

    /**
     * @var string
     */
    protected $states;

    /**
     * @var string
     */
    protected $hash;

    /**
     * Create a new command instance.
     *
     * @param Filesystem  $files
     * @param Application $app
     */
    public function __construct(Filesystem $files, Application $app)
    {
        parent::__construct();

        $this->files = $files;

        $this->path = dirname(__DIR__).'/data/en';

        $this->loader = new FileLoader($files, dirname(__DIR__).'/data');

        $config = $app->make('config');
        $this->countries = $config->get('states.countries');
        $this->states = $config->get('states.states');

        if (! $this->files->isDirectory(dirname(storage_path(self::INSTALL_HISTORY)))) {
            $this->files->makeDirectory(dirname(storage_path(self::INSTALL_HISTORY)), 0755, true);
        }

        if ($this->files->exists(storage_path(self::INSTALL_HISTORY))) {
            $this->hash = $this->files->get(storage_path(self::INSTALL_HISTORY));
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $countries = $this->files->files($this->path);

        $states = [];

        foreach ($countries as $countryDirectory) {
            list($country, $_) = explode('.', $this->last(explode(DIRECTORY_SEPARATOR, $countryDirectory)));
            $data = $this->loader->load($country, 'en');
            foreach ($data as $key => $name) {
                $states[] = [
                    'name'       => $name,
                    'code'       => "${country} ${key}",
                    'country_id' => "${country}",
                ];
            }
        }

        $states = Collection::make($states);
        $hash = md5($states->toJson());

        if (! $this->option('force') && $hash === $this->hash) {
            $this->line('No new state.');

            return false;
        }

        $stateCodes = $states->pluck('code');

        $countryCodes = $states->pluck('country_id')->unique();
        $countryIDs = Collection::make(DB::table($this->countries)->whereIn('code', $countryCodes)->pluck('id', 'code'));

        $states = $states->map(function ($item) use ($countryIDs) {
            $item['country_id'] = $countryIDs->get($item['country_id']);

            return $item;
        });

        $existingStateIDs = Collection::make(DB::table($this->states)->whereIn('code', $stateCodes)->pluck('id', 'code'));
        $states = $states->map(function ($item) use ($existingStateIDs) {
            if ($existingStateIDs->has($item['code'])) {
                $item['id'] = $existingStateIDs->get($item['code']);
            }

            return $item;
        });

        $states = $states->groupBy(function ($item) {
            return array_has($item, 'id') ? 'update' : 'create';
        });

        DB::transaction(function () use ($states, $hash) {
            $create = Collection::make($states->get('create'));
            $update = Collection::make($states->get('update'));

            foreach ($create->chunk(static::QUERY_LIMIT) as $entries) {
                DB::table($this->states)->insert($entries->toArray());
            }

            foreach ($update as $entries) {
                DB::table($this->states)->where('id', $entries['id'])->update($entries);
            }
            $this->line("{$create->count()} states created. {$update->count()} states updated.");
            $this->files->put(storage_path(static::INSTALL_HISTORY), $hash);
        });
    }

    private function last(array $data)
    {
        if (empty($data)) {
            throw new \Exception("$data should not be empty.");
        }

        return $data[count($data) - 1];
    }
}

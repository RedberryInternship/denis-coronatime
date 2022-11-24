<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Statistics;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;

class UpdateStatisticsCommand extends Command
{
	protected $signature = 'db:update-stats';

	protected $description = 'Update statistics for each country and update Worldwide statistics';

	public function handle()
	{
		DB::beginTransaction();
		Country::getQuery()->delete();
		foreach ($this->fetchCountries() as $countryData)
		{
			$country = Country::create([
				'name' => $countryData['name'],
				'code' => $countryData['code'],
			]);
			$country->save();

			$this->addCountryStatistics($country->id, $country->code);
		}
		DB::commit();

		$this->info(date('Y-m-d H:i:s') . " Country statistics have been updated successfully! \n");
		return Command::SUCCESS;
	}

	protected function fetchCountries()
	{
		$response = Http::get('https://devtest.ge/countries')->body();
		return json_decode($response, true);
	}

	protected function addCountryStatistics($countryId, $countryCode)
	{
		$statisticsData = $this->fetchCountryStatistics($countryCode);
		Statistics::create(array_merge($statisticsData, ['country_id' => $countryId]))->save();
	}

	protected function fetchCountryStatistics($countryCode)
	{
		$response = Http::post('https://devtest.ge/get-country-statistics', [
			'code' => $countryCode,
		])->body();
		$result = json_decode($response, true);
		return array_diff_key($result, array_flip(['id', 'country', 'code', 'critical', 'created_at', 'updated_at']));
	}
}

<?php

declare(strict_types = 1);

namespace JobPortal\Modules\api;

use Core\Storm;
use Core\Model;
use JobPortal\CategoryList;
use JobPortal\StatesList;

class ApiModel extends Model
{

    public function params(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        // TODO: forms filled through ajax will come here, need to make sure we access the correct model and validate through that.
        return $this->rules;
    }

    public function getDisplayName(): string
    {
        return "api";
    }

    public function citiesList(): array
    {
        $citiesList = Storm::getStorm()->getModuleInstance('City');
        $byId = Storm::getStorm()->request->getBody()['state_id'] ?? false;
        $cities = [];
        if ($byId !== false) {
            settype($byId, 'int');
            foreach ($citiesList->getModel()->getCitiesListByStateId($byId) as $city) {
                $cities[] = $city;
            }
            return $cities;
        }
        foreach ($citiesList->getModel()->getCitiesList() as $city) {
            $cities[] = $city;
        }
        return $cities;
    }

    public function categoriesList()
    {
        $categoriesList = Storm::getStorm()->getModuleInstance('Category');
        return array_values($categoriesList->getModel()->getCategoriesList());
    }

    public function statesList(): array
    {
        $statesList = Storm::getStorm()->getModuleInstance('State');
        $byId = Storm::getStorm()->request->getBody()['country_id'] ?? false;
        $states = [];
        if ($byId !== false) {
            settype($byId, 'int');
            foreach ($statesList->getModel()->getStatesListByCountry($byId) as $state) {
                $states[] = $state;
            }
            return $states;
        }

        foreach ($statesList->getStatesList() as $state) {
            $states[] = $state;
        }
        return $states;
    }
}


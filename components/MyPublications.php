<?php namespace Bree7e\Cris\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Bree7e\Cris\Models\Publication;

class MyPublications extends ComponentBase
{
    public $publications;
    public $addedPublications;

    public function componentDetails()
    {
        return [
            'name'        => 'Мои публикации',
            'description' => 'Привязанные и добавленные публикации'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function prepareData()
    {   
        // Get the signed in user
        $frontEndUser = Auth::getUser();

        $this->publications = Publication::ofAuthors([$frontEndUser->id])
            ->orderBy('year','desc')
            ->get();

        $this->addedPublications = Publication::addedByAuthor($frontEndUser->id)
            ->orderBy('year','desc')
            ->get();
    }

    public function prepareData2()
    {
        // $frontEndUser = $this->param('id'); // вынести в properties
        $id = $this->property('id');

        $frontUser = Auth::getUser();

        if ($frontUser) {}

        $publications = Publication::ofAuthors([$frontUser->id])
            ->orderBy('year', 'desc')
            ->get();

        // сгруппировать публикации по годам
        $groupedByYear = [];
        foreach ($publications as $p) {
            $groupedByYear[$p->year][] = $p;
        }

        $this->publicationsByYears = $groupedByYear;

        // $this->projects = Project::ofAuthor();
    }

    public function onRun()
    {
        $this->prepareData();
    }

}

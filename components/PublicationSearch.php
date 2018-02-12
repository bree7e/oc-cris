<?php namespace Bree7e\Cris\Components;

use Bree7e\Cris\Models\Author;
use Bree7e\Cris\Models\Department;
use Bree7e\Cris\Models\Project;
use Bree7e\Cris\Models\Publication;
use Bree7e\Cris\Models\PublicationType;
use Cms\Classes\ComponentBase;

class PublicationSearch extends ComponentBase
{
    public $departments;
    public $authors;
    public $types;
    public $projects;

    public function componentDetails()
    {
        return [
            'name' => 'Поиск публикаций',
            'description' => 'No description provided yet...',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->getDepartments();
        $this->getAuthors();
        $this->getTypes();
        $this->getProjects();
        // Bibliography::main();
    }

    public function getDepartments()
    {
        $this->departments = Department::all();
    }

    public function getTypes()
    {
        $this->types = PublicationType::all();
    }

    public function getProjects()
    {
        $this->projects = Project::all();
    }

    public function getAuthors()
    {
        $this->authors = Author::orderBy('surname')->get();
    }

    public function onGetPublications()
    {
        $fromYear = post('fromYear') ?? 1900;
        $toYear = post('toYear') ?? 2100;
        $department = post('department') ?? 0; // id
        $author = post('author') ?? 0; // id
        $type = post('type') ?? "0"; // 1,2,3,...
        $index = post('index') ?? "all"; // all,wos,scopus,risc
        $project = post('project') ?? 0; //id

        // если указан автор, то не учитвать подразделение
        if ($author > 0) {
            unset($department);
        }

        if (empty($department) || $department == 0) {
            $query = Publication::query();
        } else {
            $query = Publication::ofDepartment($department);
        }

        if (empty($fromYear)) {
            $fromYear = 1900;
        }

        if (empty($toYear)) {
            $toYear = 2100;
        }

        $query = $query->betweenYears($fromYear, $toYear);

        if ($index !== 'all') {
            switch ($index) {
                case 'wos':
                    $query = $query->isWos();
                    break;
                case 'scopus':
                    $query = $query->isScopus();
                    break;
                case 'risc':
                    $query = $query->isRisc();
                    break;
            }
        }

        if ($project > 0) {
            $query = $query->ofProject($project);
        }

        if ($type > 0) {
            $query = $query->filterByType([$type]);
        }

        if ($author > 0) {
            $query = $query->ofAuthors([$author]);
        }

        $publications = $query->get()->sortBy('authors');

        if ($publications->isEmpty()) {
            unset($this->page['publications']);
        } else {
            $this->page['publications'] = $publications;
        }

    }
}

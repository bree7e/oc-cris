<?php namespace Bree7e\Cris\Models;

use Backend\Models\ImportModel;
use Bree7e\Cris\Models\Author;
use Bree7e\Cris\Models\Project;
use Bree7e\Cris\Models\ProjectType;

/**
 * ProjectImport ImportModel
 * The class must define a method called importData used
 * for processing the imported data. The first parameter
 * $results will contain an array containing the data to import.
 */
class ProjectImport extends ImportModel
{
    /**
     * @var array Rules
     */
    public $rules = [];

    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {

            $projectType = ProjectType::getIdbyName($data['project_type']);
            if ($projectType > 0) {
                $data['project_type_id'] = $projectType;
            }
            unset($data['project_type']);

            $leader = Author::getSuggestions($data['leader'])->first();
            unset($data['leader']);
            if ($leader) {
                $data['rb_user_id'] = $leader->id;
            }

            try {
                $project = new Project();
                $project->fill($data);
                $project->save();
                $this->logCreated();
            } catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }
}

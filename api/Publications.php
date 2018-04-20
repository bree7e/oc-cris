<?php namespace Bree7e\Cris\Api;

use Backend\Classes\Controller;
use Bree7e\Cris\Models\Publication;
use Bree7e\Cris\Resources\Publication as PublicationResource;

/**
 * Publications Back-end API Controller
 */
class Publications extends Controller
{
    // protected $publications;

    // public function __construct(Publication $publications)
    // {
    //     parent::__construct();
    //     $this->publications = $publications;
    // }

    /**
     * Display a listing of the resource.

     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page = input('page', 1);
        $pageSize = 10;

        $publications = Publication::paginate($pageSize, $page);

        $publications->transform(function (Publication $publication) {
            return (new PublicationResource($publication));
        });

        return $publications;
    }

    /**
     * Display the specified resource.
     *
     * @param  \Bree7e\Cris\Models\Publication  $publication
     * @return \Illuminate\Http\Response
     */
    // public function show(Publication $publication)
    public function show($id)
    {
        $publication = Publication::findOrFail($id);

        PublicationResource::withoutWrapping();
        return new PublicationResource($publication);
    }
}

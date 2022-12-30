<?php

namespace App\Http\Livewire;

use App\Models\Competition;
use App\Models\CompetitionRecommendation;
use App\Models\CourseBooking;
use App\Models\Orphan;
use App\Models\Orphanage;
use App\Models\OrphanCourseBooking;
use App\Models\OrphanCr;
use Livewire\Component;

class DetailCompetitionRecommendation extends Component
{
    public $competition_recommendation_id;
    public $orphanageDropdownSort;
    public $orphans;
    public $orphanDropdownSort;
    public $orphanCrs;
    public $editedOrphanCrIndex;
    public $showFormConfirmation;
    public $orphanDescription;

    public function render()
    {
        $competitionRecommendations = [];
        if (auth()->user()->orphanage) {
            $this->orphanCrs = [];
            $this->competitionRecommendation = CompetitionRecommendation::find($this->competition_recommendation_id);
            $this->orphanCrs = OrphanCr::whereIn('orphan_id', Orphan::where('orphanage_id', auth()->user()->orphanage->id)->pluck('id'))
                ->where('competition_recommendation_id', $this->competition_recommendation_id)->get();
            $orphanCrs = $this->orphanCrs;
        } else {
            $this->orphanages = [];
            $this->orphans = [];

            $this->competitionRecommendation = Competition::find($this->competition_recommendation_id);
            $getCourses = auth()->user()->tutor->courses->pluck('id')->toArray();
            $getOrphanageId = CourseBooking::whereIn('course_id', $getCourses)->whereIn('status', ['ongoing', 'complete'])->pluck('orphanage_id')->toArray();
            $this->orphanages = Orphanage::whereIn('id', $getOrphanageId)->orderBy('name', 'ASC')->get();

            if ($this->orphanageDropdownSort) {
                $getOrphanId = OrphanCourseBooking::whereIn('course_booking_id', CourseBooking::whereIn('course_id', $getCourses)->whereIn('status', ['ongoing', 'complete'])->pluck('id')->toArray())
                ->pluck('orphan_id')->toArray();
                $this->orphans = Orphan::whereIn('id', $getOrphanId)->
                where('orphanage_id', $this->orphanageDropdownSort)
                ->orderBy('name', 'ASC')->get();
                if (!$this->orphanDropdownSort) {
                    $this->setOrphanDropdownSort($this->orphans->first()->id);
                }
            }

            $this->competitionRecommendations = CompetitionRecommendation::where('competition_id', $this->competition_recommendation_id)
            ->where('tutor_id', auth()->user()->tutor->id)->pluck('id')->toArray();

            $this->orphanCrs = OrphanCr::whereIn('competition_recommendation_id', $this->competitionRecommendations)
            ->orderBy('updated_at', 'DESC')->get()->toArray();
        }

        return view('livewire.detail-competition-recommendation');
    }

    public function mount($competition_recommendation_id)
    {
        $this->competition_recommendation_id = $competition_recommendation_id;
        $this->editedCrIndex = null;
        $this->showFormConfirmation = false;

        $this->competitionRecommendation = Competition::find($this->competition_recommendation_id);
        $getCourses = auth()->user()->tutor->courses->pluck('id')->toArray();
        $getOrphanageId = CourseBooking::whereIn('course_id', $getCourses)->whereIn('status', ['ongoing', 'complete'])->pluck('orphanage_id')->toArray();
        $this->orphanages = Orphanage::whereIn('id', $getOrphanageId)->orderBy('name', 'ASC')->get();
        $this->setOrphanageDropdownSort($this->orphanages->first()->id);
    }

    public function setOrphanageDropdownSort($orphanageDropdownSortNew)
    {
        $this->orphanageDropdownSort = $orphanageDropdownSortNew;
    }

    public function setOrphanDropdownSort($orphanDropdownSortNew)
    {
        $this->orphanDropdownSort = $orphanDropdownSortNew;
    }

    public function openModalConfirmation($orphanCrIndex, $keterangan)
    {
        $this->orphanCr = $this->orphanCrs[$orphanCrIndex] ?? null;
        $this->keterangan = $keterangan;
        $this->showFormConfirmation = true;
    }

    public function closeModalConfirmation()
    {
        $this->showFormConfirmation = false;
    }

    public function saveOrphanCr()
    {
        if (!is_null($this->orphanCr)) {
            OrphanCr::find($this->orphanCr['id'])->update($this->orphanCr);
        }
        $this->showFormConfirmation = false;
        $this->editedOrphanCrIndex = null;
    }

    public function deleteOrphanCr()
    {
        if (!is_null($this->orphanCr)) {
            OrphanCr::find($this->orphanCr['id'])->delete();
        }

        $this->showFormConfirmation = false;
        $this->editedOrphanCrIndex = null;
    }

    public function addData()
    {
        //nti diisi
        // $this->validate([
        //     'name' => 'required',
        //     'date_of_birth' => 'required',
        //     'gender' => 'required',
        // ], [
        //     'name.required' => 'Nama harus diisi.',
        //     'date_of_birth.required' => 'Tanggal lahir harus diisi.',
        //     'gender.required' => 'Jenis kelamin harus diisi.',
        // ]);

        // $skill = Skill::find($this->skill);

        // ///nti diisi

        $cr = Competition::find($this->competition_recommendation_id)->competitionRecommendations()->create([
        'tutor_id' => auth()->user()->tutor->id,
        'orphanage_id' => $this->orphanageDropdownSort,
        ]);

        $cr->OrphanCrs()->create([
            'orphan_id' => $this->orphanDropdownSort,
            'description' => 'mmm',
        ]);

        //$this->showForm = false;
        // reset form fields
        // $this->reset();

        // show success message
       // session()->flash('message', 'Kursus berhasil ditambahkan.');
    }

    public function editOrphanCr($orphanCrIndex)
    {
        $this->editedOrphanCrIndex = $orphanCrIndex;
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
    }

    public function findOrphan($id)
    {
        return Orphan::find($id);
    }
}

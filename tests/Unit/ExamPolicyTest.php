<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\User;
use App\Policies\ExamPolicy;
use Tests\TestCase;

/**
 * ExamPolicy is the only authorization policy in the whole application —
 * it is the sole barrier keeping one professor from viewing, editing or
 * deleting another professor's exam. Pure ownership checks, no DB needed.
 */
class ExamPolicyTest extends TestCase
{
    private ExamPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ExamPolicy;
    }

    public function test_owner_can_view_update_and_delete_their_exam(): void
    {
        $owner = new User;
        $owner->id = 1;
        $exam = new Exam(['user_id' => 1]);

        $this->assertTrue($this->policy->view($owner, $exam));
        $this->assertTrue($this->policy->update($owner, $exam));
        $this->assertTrue($this->policy->delete($owner, $exam));
    }

    public function test_non_owner_cannot_view_update_or_delete_the_exam(): void
    {
        $intruder = new User;
        $intruder->id = 2;
        $exam = new Exam(['user_id' => 1]);

        $this->assertFalse($this->policy->view($intruder, $exam));
        $this->assertFalse($this->policy->update($intruder, $exam));
        $this->assertFalse($this->policy->delete($intruder, $exam));
    }
}

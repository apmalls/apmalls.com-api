<?php

namespace Tests\Feature;

use App\Models\ProjectFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_review_is_fabricated_before_owner_submission(): void
    {
        $this->getJson('/api/v1/website/project-feedback')->assertOk()->assertJsonPath('data', null);
        $this->assertDatabaseCount('project_feedback', 0);
    }

    public function test_workspace_read_requires_active_verified_super_admin(): void
    {
        $this->getJson('/api/v1/admin/project-feedback')->assertUnauthorized();
        foreach (['Admin', 'Store Manager', 'Customer', 'Cashier', 'Delivery Boy'] as $role) {
            $this->signIn($role);
            $this->getJson('/api/v1/admin/project-feedback')->assertForbidden();
        }
        $user = $this->signIn();
        $user->update(['is_active' => false]);
        $this->getJson('/api/v1/admin/project-feedback')->assertForbidden();
        $user->update(['is_active' => true, 'email_verified_at' => null]);
        $this->getJson('/api/v1/admin/project-feedback')->assertForbidden();
        $user->update(['email_verified_at' => now()]);
        $this->getJson('/api/v1/admin/project-feedback')->assertOk()->assertJsonPath('data', null);
        $this->assertDatabaseCount('project_feedback', 0);
    }

    public function test_workspace_hydrates_existing_review_without_modifying_it(): void
    {
        $this->signIn();
        $data = $this->payload();
        $data['ratings']['handover'] = 3;
        $data['comment'] = 'Owner-approved review.';
        $saved = $this->putJson('/api/v1/admin/project-feedback', $data)->assertOk()->json('data');
        $before = ProjectFeedback::find('ap-malls')->getAttributes();
        $read = $this->getJson('/api/v1/admin/project-feedback')->assertOk()->assertJsonPath('data.overall_score', 4.6);
        $this->assertStringContainsString('no-store', $read->headers->get('Cache-Control'));
        $this->assertSame($saved, $read->json('data'));
        $this->assertSame($saved, $this->getJson('/api/v1/website/project-feedback')->json('data'));
        $this->assertSame($before, ProjectFeedback::find('ap-malls')->getAttributes());
        $this->deleteJson('/api/v1/admin/project-feedback')->assertOk();
        $this->getJson('/api/v1/admin/project-feedback')->assertOk()->assertJsonPath('data', null);
    }

    public function test_all_five_ratings_and_owner_approval_are_required(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->signIn();
        foreach (array_keys(ProjectFeedback::ASPECTS) as $key) {
            $data = $this->payload();
            unset($data['ratings'][$key]);
            $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable()->assertJsonValidationErrors('ratings.'.$key);
            foreach ([0, 6, 4.5, null, 'excellent'] as $invalid) {
                $data = $this->payload();
                $data['ratings'][$key] = $invalid;
                $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable();
            }
        }
        $data = $this->payload();
        $data['owner_approved'] = false;
        $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable()->assertJsonValidationErrors('owner_approved');
        unset($data['owner_approved']);
        $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable();
        $data = $this->payload();
        $data['ratings']['extra'] = 5;
        $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable();
        $this->assertDatabaseCount('project_feedback', 0);
    }

    public function test_average_is_calculated_and_five_stars_are_not_forced(): void
    {
        $user = $this->signIn();
        $data = $this->payload();
        $data['overall_score'] = 1;
        $data['reviewer'] = ['name' => 'Forged name'];
        $response = $this->putJson('/api/v1/admin/project-feedback', $data)->assertOk()
            ->assertJsonPath('data.overall_score', 5)->assertJsonPath('data.max_score', 5)
            ->assertJsonPath('data.reviewer.name', 'Amar Bhagat');
        $this->assertCount(5, $response->json('data.ratings'));
        $this->assertSame($user->id, ProjectFeedback::find('ap-malls')->submitted_by);
        $createdAt = ProjectFeedback::find('ap-malls')->created_at;
        $data['ratings']['usability'] = 4;
        $data['comment'] = '  Useful system.  ';
        $this->putJson('/api/v1/admin/project-feedback', $data)->assertOk()->assertJsonPath('data.overall_score', 4.8)
            ->assertJsonPath('data.comment', 'Useful system.');
        $this->assertDatabaseCount('project_feedback', 1);
        $this->assertTrue(ProjectFeedback::find('ap-malls')->created_at->equalTo($createdAt));
        $public = $this->getJson('/api/v1/website/project-feedback')->assertOk()->assertJsonPath('data.overall_score', 4.8);
        $this->assertArrayNotHasKey('submitted_by', $public->json('data'));
        $this->assertStringNotContainsString($user->email, $public->getContent());
    }

    public function test_authentication_role_active_and_verified_checks(): void
    {
        $this->putJson('/api/v1/admin/project-feedback', $this->payload())->assertUnauthorized();
        $this->deleteJson('/api/v1/admin/project-feedback')->assertUnauthorized();
        $this->signIn('Admin');
        $this->putJson('/api/v1/admin/project-feedback', $this->payload())->assertForbidden();
        $this->deleteJson('/api/v1/admin/project-feedback')->assertForbidden();
        $user = $this->signIn();
        $user->update(['is_active' => false]);
        $this->putJson('/api/v1/admin/project-feedback', $this->payload())->assertForbidden();
        $user->update(['is_active' => true, 'email_verified_at' => null]);
        $this->putJson('/api/v1/admin/project-feedback', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('project_feedback', 0);
    }

    public function test_comment_limit_and_withdrawal(): void
    {
        $this->signIn();
        $data = $this->payload();
        $data['comment'] = str_repeat('x', 1001);
        $this->putJson('/api/v1/admin/project-feedback', $data)->assertUnprocessable()->assertJsonValidationErrors('comment');
        $this->putJson('/api/v1/admin/project-feedback', $this->payload())->assertOk();
        $this->deleteJson('/api/v1/admin/project-feedback')->assertOk();
        $this->getJson('/api/v1/website/project-feedback')->assertJsonPath('data', null);
        $this->deleteJson('/api/v1/admin/project-feedback')->assertOk();
        $this->assertDatabaseCount('project_feedback', 0);
    }

    public function test_migration_rolls_back_and_recreates_table(): void
    {
        $migration = require database_path('migrations/2026_10_07_140000_create_project_feedback_table.php');
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('project_feedback'));
        $migration->up();
        $this->assertDatabaseCount('project_feedback', 0);
    }

    private function payload(): array
    {
        return ['ratings' => array_fill_keys(array_keys(ProjectFeedback::ASPECTS), 5), 'comment' => null, 'owner_approved' => true];
    }

    private function signIn(string $role = 'Super Admin'): User
    {
        $key = uniqid('feedback-');
        $user = User::create([
            'first_name' => 'Owner', 'last_name' => 'Test', 'username' => $key, 'email' => $key.'@example.com',
            'mobile' => '9'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => 'test-password', 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        Sanctum::actingAs($user);
        return $user;
    }
}

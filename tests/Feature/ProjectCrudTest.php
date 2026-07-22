<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default admin user
        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test that guest is redirected to login page.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Test project creation and initial status update tracking.
     */
    public function test_admin_can_create_project_and_status_update_is_logged(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/projects', [
                'name' => 'Test Project',
                'description' => 'A project for automated testing.',
                'status' => 'planning',
                'progress' => 10,
                'category' => 'web',
                'live_url' => 'https://example.com',
            ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => 'planning',
            'progress' => 10,
            'category' => 'web',
        ]);

        $project = Project::where('name', 'Test Project')->first();

        // Ensure the initial status history was tracked
        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'old_status' => null,
            'new_status' => 'planning',
            'note' => 'Project initialized.',
        ]); 
    }

    /**
     * Test editing a project status records a timeline log update.
     */
    public function test_project_status_update_records_log_with_custom_note(): void
    {
        $project = Project::create([
            'name' => 'Existing Project',
            'slug' => 'existing-project',
            'status' => 'in_progress',
            'progress' => 40,
        ]);

        // Record initial log manually since we created it directly
        ProjectUpdate::create([
            'project_id' => $project->id,
            'old_status' => null,
            'new_status' => 'in_progress',
            'note' => 'Initial setup.',
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/projects/{$project->id}", [
                'name' => 'Existing Project',
                'status' => 'done',
                'progress' => 100,
                'status_note' => 'Deployment successful, launching.',
            ]);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'done',
            'progress' => 100,
        ]);

        // Ensure the status transition history log was tracked
        $this->assertDatabaseHas('project_updates', [
            'project_id' => $project->id,
            'old_status' => 'in_progress',
            'new_status' => 'done',
            'note' => 'Deployment successful, launching.',
        ]);
    }

    /**
     * Test project creation with a valid Git repository link.
     */
    public function test_admin_can_create_project_with_git_link(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/projects', [
                'name' => 'Git Link Project',
                'description' => 'Testing parsing.',
                'status' => 'planning',
                'progress' => 10,
                'category' => 'web',
                'repo_link' => 'https://github.com/google/guava.git',
            ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('projects', [
            'name' => 'Git Link Project',
            'repo_owner' => 'google',
            'repo_name' => 'guava',
        ]);
    }

    /**
     * Test project creation with an invalid Git repository link.
     */
    public function test_admin_cannot_create_project_with_invalid_git_link(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/projects', [
                'name' => 'Invalid Git Project',
                'description' => 'Testing parsing failure.',
                'status' => 'planning',
                'progress' => 10,
                'category' => 'web',
                'repo_link' => 'invalid-link-format',
            ]);

        $response->assertSessionHasErrors(['repo_link']);
        $this->assertDatabaseMissing('projects', [
            'name' => 'Invalid Git Project',
        ]);
    }

    /**
     * Test project update parses the Git repository link correctly.
     */
    public function test_admin_can_update_project_git_link(): void
    {
        $project = Project::create([
            'name' => 'Updatable Project',
            'slug' => 'updatable-project',
            'status' => 'in_progress',
            'progress' => 40,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/projects/{$project->id}", [
                'name' => 'Updatable Project',
                'status' => 'in_progress',
                'progress' => 50,
                'repo_link' => 'git@github.com:laravel/laravel.git',
            ]);

        $response->assertRedirect("/projects/{$project->id}");
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'repo_owner' => 'laravel',
            'repo_name' => 'laravel',
        ]);
    }

    /**
     * Test reordering projects via POST API.
     */
    public function test_admin_can_reorder_projects(): void
    {
        $project1 = Project::create(['name' => 'Project A', 'status' => 'in_progress', 'progress' => 10, 'order' => 1]);
        $project2 = Project::create(['name' => 'Project B', 'status' => 'in_progress', 'progress' => 20, 'order' => 2]);
        $project3 = Project::create(['name' => 'Project C', 'status' => 'in_progress', 'progress' => 30, 'order' => 3]);

        // Send a request to swap visual order of project1 and project3
        // So visually: project3 (pos 0), project2 (pos 1), project1 (pos 2)
        $response = $this->actingAs($this->admin)
            ->postJson('/api/projects/reorder', [
                'order' => [
                    ['id' => $project3->id, 'position' => 0],
                    ['id' => $project2->id, 'position' => 1],
                    ['id' => $project1->id, 'position' => 2],
                ]
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verify the database order:
        // Project3 should have order 1, Project2 order 2, Project1 order 3
        $this->assertEquals(1, $project3->fresh()->order);
        $this->assertEquals(2, $project2->fresh()->order);
        $this->assertEquals(3, $project1->fresh()->order);
    }
}

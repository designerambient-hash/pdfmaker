<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfEditorFeatureTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test unauthenticated access redirects to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        auth()->logout();

        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $docRes = $this->get('/documents/non-existent-id/edit');
        $docRes->assertRedirect('/login');
    }

    /**
     * Test user login and registration flow.
     */
    public function test_user_registration_and_login(): void
    {
        auth()->logout();

        $email = 'alice_' . uniqid() . '@example.com';

        $regResponse = $this->post('/register', [
            'name' => 'Alice Doe',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $regResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => $email]);

        auth()->logout();

        $loginResponse = $this->post('/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    /**
     * Test STRICT user isolation: User B cannot access User A's document.
     */
    public function test_user_cannot_access_or_modify_another_users_document(): void
    {
        $otherUser = User::factory()->create();

        $otherDoc = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $otherUser->id,
            'title' => 'User A Secret Project',
            'width' => 794,
            'height' => 1123,
            'status' => 'active',
        ]);

        $otherPage = DocumentPage::create([
            'document_id' => $otherDoc->id,
            'page_number' => 1,
            'width' => 794,
            'height' => 1123,
        ]);

        // Attempting to edit other user's document must return 404
        $editRes = $this->get("/documents/{$otherDoc->id}/edit");
        $editRes->assertStatus(404);

        // Attempting to update other user's document
        $updateRes = $this->putJson("/documents/{$otherDoc->id}", [
            'title' => 'Hacked Title',
        ]);
        $updateRes->assertStatus(404);
        $this->assertEquals('User A Secret Project', $otherDoc->fresh()->title);

        // Attempting to add page to other user's document
        $addPageRes = $this->postJson("/documents/{$otherDoc->id}/pages", [
            'width' => 794,
            'height' => 1123,
        ]);
        $addPageRes->assertStatus(404);

        // Attempting to save canvas data to other user's document
        $saveRes = $this->postJson("/documents/{$otherDoc->id}/save", [
            'title' => 'Malicious Save',
            'pages' => [
                ['id' => $otherPage->id, 'canvas_data' => '{}'],
            ],
        ]);
        $saveRes->assertStatus(404);

        // Attempting to delete other user's document
        $deleteRes = $this->deleteJson("/documents/{$otherDoc->id}");
        $deleteRes->assertStatus(404);
        $this->assertEquals('active', $otherDoc->fresh()->status);
    }

    /**
     * Test Dashboard index and tabs.
     */
    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('PDFCraft');
        $response->assertSee('All Documents');
    }

    /**
     * Test Templates Library page.
     */
    public function test_templates_page_renders_successfully(): void
    {
        $response = $this->get('/templates');
        $response->assertStatus(200);
        $response->assertSee('Templates');
    }

    /**
     * Test PDF Tools Hub.
     */
    public function test_tools_page_renders_successfully(): void
    {
        $response = $this->get('/tools');
        $response->assertStatus(200);
        $response->assertSee('Merge PDF');
        $response->assertSee('Split PDF');
    }

    /**
     * Test Document Creation with custom parameters.
     */
    public function test_can_create_document_and_open_editor(): void
    {
        $uniqueTitle = 'My New Annual Report ' . uniqid();
        $response = $this->post('/documents', [
            'title' => $uniqueTitle,
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => $uniqueTitle,
            'user_id' => $this->user->id,
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ]);

        $doc = $this->user->documents()->where('title', $uniqueTitle)->first();
        $this->assertNotNull($doc);
        $this->assertCount(1, $doc->pages);

        // Open editor
        $editorRes = $this->get("/documents/{$doc->id}/edit");
        $editorRes->assertStatus(200);
        $editorRes->assertSee($uniqueTitle);
    }

    /**
     * Test Page Management: Add page, duplicate page, and delete page.
     */
    public function test_page_management_operations(): void
    {
        $doc = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Multi-page Test',
            'width' => 794,
            'height' => 1123,
        ]);

        $page1 = DocumentPage::create([
            'document_id' => $doc->id,
            'page_number' => 1,
            'width' => 794,
            'height' => 1123,
            'background_color' => '#ffffff',
            'canvas_data' => json_encode(['objects' => []]),
        ]);

        // Add page 2
        $res = $this->postJson("/documents/{$doc->id}/pages", [
            'width' => 794,
            'height' => 1123,
            'background_color' => '#f8fafc',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(2, $doc->fresh()->pages()->count());

        // Duplicate page 1
        $resDup = $this->postJson("/documents/{$doc->id}/pages/{$page1->id}/duplicate");
        $resDup->assertStatus(200);
        $this->assertEquals(3, $doc->fresh()->pages()->count());

        // Delete page 1
        $resDel = $this->deleteJson("/documents/{$doc->id}/pages/{$page1->id}");
        $resDel->assertStatus(200);
        $this->assertEquals(2, $doc->fresh()->pages()->count());
    }

    /**
     * Test Document Duplication (independent ID, copy of all pages).
     */
    public function test_document_duplication(): void
    {
        $doc = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Original Blueprint',
            'width' => 794,
            'height' => 1123,
        ]);

        DocumentPage::create([
            'document_id' => $doc->id,
            'page_number' => 1,
            'width' => 794,
            'height' => 1123,
            'canvas_data' => json_encode(['version' => '5.3.0', 'objects' => [['type' => 'rect']]]),
        ]);

        $res = $this->postJson("/documents/{$doc->id}/duplicate");
        $res->assertStatus(200);
        $res->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'title' => 'Copy of Original Blueprint',
        ]);
    }

    /**
     * Test Document Trash, Restore, and Star.
     */
    public function test_document_trash_and_star_toggle(): void
    {
        $doc = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Document to Trash',
            'width' => 794,
            'height' => 1123,
        ]);

        // Star toggle
        $resStar = $this->postJson("/documents/{$doc->id}/star");
        $resStar->assertStatus(200);
        $resStar->assertJsonFragment(['is_starred' => true]);

        // Move to trash
        $resTrash = $this->deleteJson("/documents/{$doc->id}");
        $resTrash->assertStatus(200);
        $this->assertEquals('trash', $doc->fresh()->status);

        // Restore
        $resRestore = $this->postJson("/documents/{$doc->id}/restore");
        $resRestore->assertStatus(200);
        $this->assertEquals('active', $doc->fresh()->status);
    }

    /**
     * Test Saving Canvas Data and Creating Version History.
     */
    public function test_save_and_version_history(): void
    {
        $doc = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Document with Versions',
            'width' => 794,
            'height' => 1123,
        ]);

        $page = DocumentPage::create([
            'document_id' => $doc->id,
            'page_number' => 1,
            'width' => 794,
            'height' => 1123,
        ]);

        $resSave = $this->postJson("/documents/{$doc->id}/save", [
            'title' => 'Updated Document Title',
            'create_version' => true,
            'version_name' => 'Initial Milestone',
            'pages' => [
                [
                    'id' => $page->id,
                    'canvas_data' => json_encode(['objects' => [['type' => 'textbox', 'text' => 'Hello World']]]),
                    'width' => 794,
                    'height' => 1123,
                ],
            ],
        ]);

        $resSave->assertStatus(200);
        $this->assertEquals('Updated Document Title', $doc->fresh()->title);
        $this->assertEquals(1, $doc->versions()->count());

        // Get versions list
        $resVer = $this->getJson("/documents/{$doc->id}/versions");
        $resVer->assertStatus(200);
        $resVer->assertJsonFragment(['name' => 'Initial Milestone']);
    }

    /**
     * Test Using a Template.
     */
    public function test_create_document_from_template(): void
    {
        $tpl = Template::first();
        $this->assertNotNull($tpl);

        $res = $this->postJson("/templates/{$tpl->id}/use");
        $res->assertStatus(200);
        $res->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('documents', [
            'title' => $tpl->title . ' (From Template)',
        ]);
    }

    /**
     * Test Signatures saving and listing.
     */
    public function test_signature_management(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $resStore = $this->postJson('/signatures', [
            'name' => 'Official Seal Signature',
            'image_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ]);

        $resStore->assertStatus(200);
        $resStore->assertJsonFragment(['success' => true]);

        $resList = $this->getJson('/signatures');
        $resList->assertStatus(200);
        $resList->assertJsonFragment(['name' => 'Official Seal Signature']);
    }

    /**
     * Test Saved Colors palette.
     */
    public function test_saved_colors_palette(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $resStore = $this->postJson('/saved-colors', [
            'color_code' => '#6366f1',
            'color_type' => 'hex',
            'name' => 'Brand Indigo',
        ]);

        $resStore->assertStatus(200);
        $resStore->assertJsonFragment(['color_code' => '#6366f1']);

        $resList = $this->getJson('/saved-colors');
        $resList->assertStatus(200);
        $resList->assertJsonFragment(['color_code' => '#6366f1']);
    }

    /**
     * Test PDF Tools: Image to PDF conversion.
     */
    public function test_images_to_pdf_conversion(): void
    {
        // Create a real temporary test image
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_') . '.jpg';
        $im = imagecreatetruecolor(400, 300);
        $bg = imagecolorallocate($im, 79, 70, 229);
        imagefilledrectangle($im, 0, 0, 400, 300, $bg);
        imagejpeg($im, $tmpFile);
        imagedestroy($im);

        $image = new UploadedFile($tmpFile, 'test_slide.jpg', 'image/jpeg', null, true);

        $res = $this->post('/tools/images-to-pdf', [
            'images' => [$image],
        ]);

        $res->assertStatus(200);
        $res->assertJsonFragment(['success' => true]);

        @unlink($tmpFile);
    }

    /**
     * Test Bulk Actions (trash, restore, star, forceDelete).
     */
    public function test_bulk_actions_and_empty_trash(): void
    {
        $doc1 = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Bulk Doc 1',
            'status' => 'active',
        ]);
        $doc2 = Document::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $this->user->id,
            'title' => 'Bulk Doc 2',
            'status' => 'active',
        ]);

        // Bulk Star
        $resStar = $this->postJson('/documents/bulk-action', [
            'action' => 'star',
            'ids' => [$doc1->id, $doc2->id],
        ]);
        $resStar->assertStatus(200);
        $this->assertEquals(1, Document::find($doc1->id)->is_starred);
        $this->assertEquals(1, Document::find($doc2->id)->is_starred);

        // Bulk Trash
        $resTrash = $this->postJson('/documents/bulk-action', [
            'action' => 'trash',
            'ids' => [$doc1->id, $doc2->id],
        ]);
        $resTrash->assertStatus(200);
        $this->assertEquals('trash', Document::find($doc1->id)->status);
        $this->assertEquals('trash', Document::find($doc2->id)->status);

        // Bulk Restore
        $resRestore = $this->postJson('/documents/bulk-action', [
            'action' => 'restore',
            'ids' => [$doc1->id],
        ]);
        $resRestore->assertStatus(200);
        $this->assertEquals('active', Document::find($doc1->id)->status);

        // Move doc1 to trash as well
        $doc1->update(['status' => 'trash']);

        // Empty Trash
        $resEmpty = $this->postJson('/documents/empty-trash');
        $resEmpty->assertStatus(200);
        $resEmpty->assertJsonFragment(['success' => true]);

        // Assert both documents are permanently deleted
        $this->assertNull(Document::find($doc1->id));
        $this->assertNull(Document::find($doc2->id));
    }

    /**
     * Security Test: OWASP Security Headers.
     */
    public function test_owasp_security_headers_are_present(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Security Test: Anti-Brute-Force Rate Limiting on Login.
     */
    public function test_login_rate_limiting_locks_out_after_too_many_attempts(): void
    {
        auth()->logout();

        $email = 'victim_' . uniqid() . '@example.com';

        // Perform 5 consecutive failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt must be throttled with rate limit error message
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Too many failed login attempts', $errors[0]);
    }

    /**
     * Security Test: Uploading a fake or masked non-PDF file is rejected.
     */
    public function test_fake_pdf_upload_is_rejected(): void
    {
        // Fake PDF with PHP content
        $fakePdf = UploadedFile::fake()->createWithContent('malicious.pdf', '<?php echo "hacked"; ?>');

        $response = $this->postJson('/pdf/import', [
            'pdf_file' => $fakePdf,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    /**
     * Security Test: Prohibited XXE entities in SVG are rejected.
     */
    public function test_prohibited_xxe_entities_in_svg_are_rejected(): void
    {
        $maliciousSvg = '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg>&xxe;</svg>';
        $svgFile = UploadedFile::fake()->createWithContent('exploit.svg', $maliciousSvg);

        $response = $this->postJson('/assets/upload-image', [
            'image' => $svgFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }
}


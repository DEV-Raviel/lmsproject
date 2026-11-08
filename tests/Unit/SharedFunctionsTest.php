<?php

use PHPUnit\Framework\TestCase;

final class SharedFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testHtmlOutputIsEscaped(): void
    {
        self::assertSame('&lt;script&gt;&quot;&amp;&#039;', e('<script>"&\''));
    }

    public function testNewPasswordsUseTheSameTwelveToTwoHundredCharacterRule(): void
    {
        self::assertFalse(is_valid_new_password(str_repeat('a', 11)));
        self::assertTrue(is_valid_new_password(str_repeat('a', 12)));
        self::assertTrue(is_valid_new_password(str_repeat('a', 200)));
        self::assertFalse(is_valid_new_password(str_repeat('a', 201)));
    }

    public function testGeneratedTemporaryPasswordsAreSixteenCharacters(): void
    {
        $password = generate_temporary_password();

        self::assertSame(16, strlen($password));
        self::assertSame(1, preg_match('/\A[A-Za-z2-9!@#$%]{16}\z/', $password));
    }

    public function testFacebookProfileLinksRequireAnHttpsFacebookUrl(): void
    {
        self::assertTrue(is_valid_facebook_url('https://www.facebook.com/example.teacher'));
        self::assertTrue(is_valid_facebook_url('https://m.facebook.com/profile.php?id=123'));
        self::assertTrue(is_valid_facebook_url('https://fb.com/example.teacher'));
        self::assertFalse(is_valid_facebook_url('http://facebook.com/example.teacher'));
        self::assertFalse(is_valid_facebook_url('https://facebook.com.evil.example/profile'));
        self::assertFalse(is_valid_facebook_url('https://example.com/facebook.com'));
        self::assertFalse(is_valid_facebook_url('https://facebook.com/'));
    }

    public function testProfilePhotoPathsOnlyAllowGeneratedJpegNames(): void
    {
        self::assertSame(
            '../student/profile-photo.php?id=16&class_id=4',
            profile_photo_src(
                'uploads/profile_photos/0123456789abcdef0123456789abcdef.jpg',
                16,
                '../',
                4
            )
        );
        self::assertSame(
            '../student/profile-photo.php?id=16',
            profile_photo_src('uploads/profile_photos/0123456789abcdef0123456789abcdef.jpg', 16, '../')
        );
        self::assertNull(profile_photo_src('../config/db.php', 16, '../'));
        self::assertNull(profile_photo_src('uploads/profile_photos/profile.php', 16, '../'));
        self::assertNull(profile_photo_src(null, 16, '../'));
        self::assertNull(profile_photo_src('uploads/profile_photos/0123456789abcdef0123456789abcdef.jpg', 0, '../'));
    }

    public function testCsrfTokenValidationRequiresTheSessionToken(): void
    {
        $token = csrf_token();

        self::assertSame(64, strlen($token));
        self::assertTrue(csrf_token_is_valid($token));
        self::assertFalse(csrf_token_is_valid('incorrect'));
        self::assertFalse(csrf_token_is_valid(['not-a-token']));
    }

    public function testRenderedTablesAreEscapedAndScrollable(): void
    {
        ob_start();
        render_table(['<Column>'], [['<Value>']]);
        $html = ob_get_clean();

        self::assertIsString($html);
        self::assertStringContainsString('class="table-responsive"', $html);
        self::assertStringContainsString('&lt;Column&gt;', $html);
        self::assertStringContainsString('&lt;Value&gt;', $html);
        self::assertStringNotContainsString('<Value>', $html);
    }

    public function testStudentTaskQueriesReturnNoRowsWithoutAnAuthenticatedUser(): void
    {
        self::assertNull(current_user());
        self::assertSame([], get_tasks('assigned'));
        self::assertSame([], get_tasks('unknown'));
    }
}

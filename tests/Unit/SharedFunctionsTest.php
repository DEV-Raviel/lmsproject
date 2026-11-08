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

<?php

namespace Tests\Feature;

use Nuwave\Lighthouse\Testing\ClearsSchemaCache;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Subject;

class GraphTest extends TestCase
{
    /**
     * Create subject model and test graphql.
     *
     * @return void
     */

    public function test_create_query_destroy_subject(): void
    {

        //authenticated user
        $user = User::factory()->make();
        Sanctum::actingAs(
            $user,
        );

        $subject = Subject::factory()->create();
        // $this->testUserId = $subject->id;
        $response = $this->graphQL(/** @lang GraphQL */ '
            {
                subject(id: '.$subject->id.') {
                    name
                }
            }
        ')->assertJson([
            'data' => [
                'subject' => [
                    'name' => $subject->name,
                ],
            ],
        ]);

        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation {
                deleteSubject(id: '.$subject->id.') {
                    name
                }
            }
        ')->assertJson([
            'data' => [
                'deleteSubject' => [
                    'name' => $subject->name,
                ],
            ],
        ]);
    }

    /**
     * Try to query Users, be rejected.
     *
     * @return void
     */

    public function testQueryUsersProtected(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            {
                users {
                    data {
                        name
                    }
                }
            }
        ')->decodeResponseJson();

        $message = array_shift(json_decode($response->json)->errors)->message;
        $this->assertEquals($message, "Unauthenticated.");
    }

    /**
     * Try to query Users as authenticated user, be successful.
     *
     * @return void
     */

    public function testQueryUsersAuthenticated(): void
    {
        $user = User::factory()->make();

        Sanctum::actingAs(
            $user,
        );

        $response = $this->graphQL(/** @lang GraphQL */ '
            {
                users {
                    data {
                        name
                    }
                }
            }
        ')->decodeResponseJson();

        $users = json_decode($response->json)->data->users->data;
        $this->assertCount(1, $users);
    }

    /**
     * Try to query subjects, be rejected
     * 
     * @return void
     */

     public function testQuerySubjectsProtected(): void 
     {
         $response = $this->graphQL(/** @lang GraphQL */ '
            {
                subjects {
                    id
                    name
                }
            }
        ')->decodeResponseJson();

        $message = array_shift(json_decode($response->json)->errors)->message;
        $this->assertEquals($message, "Unauthenticated.");
     }

     /**
     * Try to query subjects as authenticated user, be successful.
     *
     * @return void
     */

    public function testQuerySubjectsAuthenticated(): void
    {
        $expected = 25;

        $user = User::factory()->make();

        Sanctum::actingAs(
            $user,
        );

        $response = $this->graphQL(/** @lang GraphQL */ '
            {
                subjects {
                    id
                    name
                }
            }
        ')->decodeResponseJson();

        $numbersOfSubjects = json_decode($response->json)->data->subjects;
        $this->assertGreaterThan($expected, $numbersOfSubjects, "It doesnt give me all data!");
    }

    /**
     * Try to find a subject, be rejected
     * 
     * @return void
     */

     public function testFindSubjectsProtected(): void 
     {
         $response = $this->graphQL(/** @lang GraphQL */ '
            {
                subject(id: "1") {
                    name
                }
            }
        ')->decodeResponseJson();

        $message = array_shift(json_decode($response->json)->errors)->message;
        $this->assertEquals($message, "Unauthenticated.");
     }

    /**
     * Try to find a subject as authenticated user, be successful.
     *
     * @return void
     */

    public function testFindSubjectsAuthenticated(): void
    {
        $expected = 1;

        $user = User::factory()->make();

        Sanctum::actingAs(
            $user,
        );

        $response = $this->graphQL(/** @lang GraphQL */ '
            {
                subject(id: "1") {
                    name
                }
            }
        ')->decodeResponseJson();

        $foundSubject = json_decode($response->json)->data->subject->name;
        //subject is not null
        $this->assertNotNull($foundSubject, "It is not exist!");
    }

    /**
     * Try to query sorted subjects by age , be rejected
     * 
     * @return void
     */

     public function testQuerySortedAgeSubjectsProtected(): void 
     {
         $response = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects (orderBy: [{column: DATE_OF_BIRTH, order:ASC}]) {
                    date_of_birth
                }
            }
        ')->decodeResponseJson();

        $message = array_shift(json_decode($response->json)->errors)->message;
        $this->assertEquals($message, "Unauthenticated.");
     }

     /**
     * Try to query sorted subjects by age as authenticated user, be successful.
     *
     * @return void
     */

    public function testQuerySortedAgeSubjectsAuthenticated(): void
    {
        $user = User::factory()->make();

        Sanctum::actingAs(
            $user,
        );

        $response0 = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects (orderBy: [{column: DATE_OF_BIRTH, order:ASC}]) {
                    date_of_birth
                }
            }
        ')->decodeResponseJson();

        $this->assertNotNull($response0, "There is nothing!");

        $sortedSubjects = json_decode($response0->json)->data->subjects;
        
        $response1 = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects {
                    date_of_birth
                }
            }
        ')->decodeResponseJson();


        $notSortedSubjects = json_decode($response1->json)->data->subjects;

        //before sorting
        $this->assertNotEquals($notSortedSubjects, $sortedSubjects, "It has not been sorted!");

        //after sorting
        sort($notSortedSubjects, SORT_REGULAR);
        $this->assertEquals($notSortedSubjects, $sortedSubjects, "It has not been sorted correctly!");

    }

        /**
     * Try to query sorted subjects by test chamber , be rejected
     * 
     * @return void
     */

     public function testQuerySortedChamberSubjectsProtected(): void 
     {
         $response = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects (orderBy: [{column: TEST_CHAMBER, order:ASC}]) {
                    test_chamber
                }
            }
        ')->decodeResponseJson();

        $message = array_shift(json_decode($response->json)->errors)->message;
        $this->assertEquals($message, "Unauthenticated.");
     }

     /**
     * Try to query sorted subjects by test chamber as authenticated user, be successful.
     *
     * @return void
     */

    public function testQuerySortedChamberSubjectsAuthenticated(): void
    {
        $user = User::factory()->make();

        Sanctum::actingAs(
            $user,
        );

        $response0 = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects (orderBy: [{column: TEST_CHAMBER, order:ASC}]) {
                    test_chamber
                }
            }
        ')->decodeResponseJson();

        $this->assertNotNull($response0, "There is nothing!");

        $sortedSubjects = json_decode($response0->json)->data->subjects;
        
        $response1 = $this->graphQL(/** @lang GraphQL */ '
            {
               subjects {
                    test_chamber
                }
            }
        ')->decodeResponseJson();


        $notSortedSubjects = json_decode($response1->json)->data->subjects;

        //before sorting
        $this->assertNotEquals($notSortedSubjects, $sortedSubjects, "It has not been sorted!");

        //after sorting
        sort($notSortedSubjects, SORT_REGULAR);
        $this->assertEquals($notSortedSubjects, $sortedSubjects, "It has not been sorted correctly!");

    }
}
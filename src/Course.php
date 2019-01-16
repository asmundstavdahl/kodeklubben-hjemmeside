<?php

class Course extends AppEntity {
    protected static $fields = [
        "id" => \integer::class,
        "name" => \string::class,
        "description" => \string::class,
        "assignments_url" => \string::class,
    ];

    public static function findAll()
    {
        return [
            new Course([
                "id" => 1,
                "name" => "Scratch",
                "description" => "Scratch er et visuelt, blokkbasert programmeringsspråk. Gjennom å sette sammen forskjellige klosser som utfører oppgaver eller har verdier, kan man blant annet lage enkle spill. Språket programmeres i et gratis program utviklet og vedlikeholdt av \"the Lifelong Kindergarten group\" på \"the MIT Media Lab\". Fordelen med Scratch er at det er mulig å se hva koden man har laget, gjør med en gang. Samtidig er det en god introduksjon til programmering. Kurset passer for nybegynnere og er anbefalt fra 4. klasse og oppover. Alt man trenger å ha med som deltaker av kurset er en PC. Det kan også være en fordel å ha med en PC-mus, men dette er ikke nødvendig.",
                "assignments_url" => "https://oppgaver.kidsakoder.no/scratch",
            ]),
            new Course([
                "id" => 2,
                "name" => "Python",
                "description" => "Python er et tekstbasert programmeringsspråk som ble utviklet som et fri-programvareprosjekt. Språket har en enkel oppbygging og er lett å lese sammenlignet med andre programmeringsspråk. Programmeringsspråket kan brukes til å lage 2D-spill, til automatisering, vitenskapelig analyse, GUI-applikasjoner og servere. I dette kurset vil det fokuseres på de grunnleggende elementene i Python ved hjelp av noe som heter Turtle. Turtle er et bibliotek, en samling av funksjoner. Kurset er anbefalt for 8. Klasse og oppover, eller for de som er ferdig med Scratch-kurset og trenger litt større utfordringer. Deltakerne må ha med egen PC, og det kan være en fordel med PC-mus, men dette er ikke nødvendig. Man får hjelp med installering av programvare under selve kurset hvis man ikke har gjort dette på forhånd.",
                "assignments_url" => "https://oppgaver.kidsakoder.no/python",
            ]),
            new Course([
                "id" => 3,
                "name" => "ComputerCraft",
                "description" => "ComputerCraft er en mod (modifisering) til Minecraft som gir deg muligheten til å bygge og programmere datamaskiner og roboter inne i Minecraft-verdenen. Inne i Minecraft programmerer vi i et språk som heter Lua, som ligner mye på Python. Lua er altså et ganske lesbart og enkelt oppsatt, tekstbasert programmeringsspråk. Disse oppgavesettene krever hverken forkunnskaper i programmering eller Minecraft, men en eller begge deler vil nok gjøre dette enda mer spennende! Kurset passer for barn og ungdommer fra 8. Klasse og oppover, eller for de som er ferdig med Scratch-kurset og trenger nye utfordringer, eller har vært borti python fra før av. Deltakerne må ha med egen PC. NB! For å kunne delta i dette kurset må du ha Java-versjonen av Minecraft installert på datamaskinen din. Det er viktig at det er Java-versjonen fordi ComputerCraft-modifikasjonen ikke fungerer med Windows-versjonen av Minecraft.",
                "assignments_url" => "https://oppgaver.kidsakoder.no/python",
            ]),
        ];
    }
}

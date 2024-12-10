<?php namespace Mk3d\Contentblocks\Updates;

use Seeder;
use Mk3d\Contentblocks\Models\Block;

class SeedLocationTable extends Seeder
{
    public function run()
    {
        $block = Block::create(
            [
                'title' => 'Evenement organiseren',
                'description' => 'Heb je een leuke locatie nodig om een feestje te vieren? Of wil je een wedstrijd organiseren? Wij hebben een gezellige kantine, ruime parkeermogelijkheden en eventueel een overdekte rijhal beschikbaar. ',
                'image' => '/icons/icon_cheers.svg',
                'type' =>  'content' 
            ]
        );
        $block = Block::create(    
            [
                'title' => 'Rijhal huren',
                'description' => 'Wil je graag een keer in een andere omgeving rijden? Of wil je toch even lekker droog oefenen voor je komende wedstrijd?',
                'image' => '/icons/icon_rijhal.svg',
                'type' =>  'content'
            ]
        );
        $block = Block::create(   
            [
                'title' => 'Pensionstalling',
                'description' => 'Heb je geen plek voor je paard of pony? Of wil je graag dat je paard of pony lekker veel buiten komt? Wij hebben verschillende mogelijkheden voor pensionstalling.',
                'image' => '/icons/icon_stable.svg',
                'type' =>  'content'
            ]
        );
        $block = Block::create(      
            [
                'title' => 'Anna Dijkstra',
                'description' => "The perfect getaway! The horseback riding was incredible, and the cabins were so cozy. Can't wait to return!",
                'image' => '/reviews/anna_review.png',
                'type' =>  'review'
            ]
        );
        $block = Block::create(  
            [
                'title' => 'Hendrik Adema',
                'description' => "Wij hebben een geweldig feest gehad in de kantine van de manege. Alles was goed geregeld en de sfeer was top!",
                'image' => '/reviews/anna_review.png',
                'type' =>  'review'
            ]
        );
        $block = Block::create(  
            [
                'title' => 'Johanna de Vries',
                'description' => "Ik heb een super leuke wedstrijd gereden in de rijhal van de manege. De locatie is top en de sfeer was heel fijn.",
                'image' => '/reviews/anna_review.png',
                'type' =>  'review'
            ]   
        );
    }
}
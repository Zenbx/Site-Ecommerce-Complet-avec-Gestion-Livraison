@extends('layouts.app')

@section('title', 'TechStorm - A propos')

@push('styles')
    @vite(['resources/css/About_css/about.css'])
@endpush

@section('content')
    <H1 class="titre"> Qui sommes nous?</H1>
      <div class="content">
        <p>Nous sommes une équipe de jeunes développeurs passionnés par la technologie et l’entrepreneuriat. Le site TechStorm est un site e-commerce innovant qui se spécialise dans la vente de produits technologiques de pointe.

          Notre mission est de fournir à nos clients les dernières innovations en matière de gadgets, d’appareils électroniques et d’accessoires, tout en offrant une expérience d’achat exceptionnelle.
          
          Derrière TechStorm, se trouve une équipe de développeurs créatifs et déterminés qui conçoivent, améliorent et perfectionnent sans cesse la plateforme pour la rendre plus rapide, intuitive et sécurisée.
          Chaque ligne de code que nous écrivons traduit notre engagement à connecter la technologie à l’humain et à simplifier le quotidien de nos utilisateurs.
          
          Nous croyons que l’innovation naît de la collaboration et de la curiosité, et nous mettons cette philosophie au cœur de chaque projet que nous entreprenons.
        </p>
      </div>
      <section class="scroll-section">
      <div class="developpers">
        <div class="dev"><img src="images/About-images/Nathan.jpeg" alt="" class="img1">
            <div>
              <p>Nathan Nzieleu</p>
              <p>...</p>
            </div></div>
        <div class="dev"><img src="images/About-images/bil.jpeg" alt="" class="img2">
          <div>
            <p>Bill Demanou</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"> <img src="images/About-images/bisseck.jpeg" alt="" class="img3">
          <div>
            <p>Chalvi Bisseck</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/raissa.jpeg" alt="" class="img4">
          <div>
            <p>Raissa Wokmeni</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/zuch.jpeg" alt="" class="img5">
          <div>
            <p>Nathanael Zuchuon</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/tomson.jpeg" alt="" class="img5">
          <div>
            <p>Thomas Tagne</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/fofack.jpeg" alt="" class="img5">
          <div>
            <p>Henri Fofack</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/thomas.jpeg" alt="" class="img5">
          <div>
            <p>Thomas Eloundou</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/angela.jpeg" alt="" class="img5">
          <div>
            <p>Angela Tomo</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/odile.jpeg" alt="" class="img5">
          <div>
            <p>Odile Ayissi</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/isa.jpeg" alt="" class="img5">
          <div>
            <p>Isabelle Magne</p>
            <p>...</p>
          </div>
        </div>
        <div class="dev"><img src="images/About-images/ivanna.jpeg" alt="" class="img5">
          <div>
            <p>Ivana Waton</p>
            <p>...</p>
          </div>
        </div>
          <div class="dev"><img src="images/About-images/jeff.jpeg" alt="" class="img5">
          <div>
            <p>Jeff Belekotan</p>
            <p>...</p>
          </div>
        </div>
      </div>
    </section>

@endsection
<?php

namespace Database\Seeders;

/**
 * Matière du jeu d'essai.
 *
 * Les textes sont écrits, pas générés : ils parlent de la vie d'un système
 * pluriel — passer le front, tenir un carnet, se répartir les rendez-vous.
 * Les posts se composent de trois fragments qui s'enchaînent proprement dans
 * n'importe quel ordre, ce qui donne des milliers de textes lisibles sans
 * répéter la même phrase.
 */
class DemoContent
{
    /** @var array<int, string> */
    public const NAMES = [
        'Kai', 'Nori', 'Sora', 'Rin', 'Haru', 'Yuki', 'Lior', 'Sacha', 'Camille', 'Noé',
        'Ilan', 'Mika', 'Alba', 'Enzo', 'Tess', 'Jun', 'Milo', 'Naël', 'Anouk', 'Elio',
        'Solal', 'Zoé', 'Marin', 'Nine', 'Awen', 'Loup', 'Iris', 'Téo', 'Nils', 'Maya',
        'Ombre', 'Petite', 'Vega', 'Silas', 'Nuage', 'Ari', 'Wren', 'Sept', 'Lune', 'Cassiel',
    ];

    /** @var array<int, string> */
    public const PRONOUNS = ['iel', 'elle', 'il', 'ael', 'iel/elle', 'il/iel', 'ul', ''];

    /** @var array<int, string> */
    public const SYSTEM_NAMES = [
        'La Maisonnée', 'Constellation', 'Le Refuge', 'Atelier commun', 'Les Veilleurs',
        'Maison bleue', 'Le Grenier', 'Collectif du jeudi', 'Les Quatre Heures', 'Archipel',
    ];

    /** @var array<int, string> */
    public const BIOS = [
        'Je tiens les carnets, les listes et les rendez-vous. Musique tard le soir.',
        "Celui qui parle aux gens quand personne d'autre n'a envie.",
        'Photo argentique, marches longues, thé qui refroidit toujours trop vite.',
        "Je m'occupe du travail et des papiers. Peu de posts, mais je lis tout.",
        'Ici pour les nuits blanches et les discussions qui partent loin.',
        'Jardin, pain, chats. Rien de plus compliqué que ça.',
        "J'apprends la basse depuis deux ans, ça s'entend de moins en moins.",
        "Je réponds lentement, ce n'est jamais contre vous.",
        "Bibliothécaire dans l'âme, désordonnée dans les faits.",
        'Je sors surtout quand il pleut. On a chacun ses horaires.',
        "Dessin, jeux de rôle, et beaucoup trop d'onglets ouverts.",
        "Je garde la mémoire des rendez-vous médicaux. Quelqu'un doit le faire.",
        "Petite voix, grandes colères. J'y travaille.",
        'Course à pied le matin, silence le reste du temps.',
        'Je cuisine pour tout le monde, même ceux qui ne mangent pas.',
        "Étudiante en histoire. Je poste surtout des trouvailles d'archives.",
        "Celui qui s'excuse trop. On me l'a dit, je continue quand même.",
        'Je tiens la porte quand les autres ont besoin de sortir.',
        'Vélo, cartes, et des projets que je ne finis jamais.',
        'Je préfère les messages privés aux posts publics. Écrivez-moi.',
    ];

    /** @var array<int, string> */
    public const POST_OPENERS = [
        'Journée calme.',
        'Réveil difficile, mais réveil quand même.',
        "Premier jour depuis longtemps où j'ai tenu toute la matinée.",
        'On a fait le ménage à trois, chacun son étage.',
        "J'ai pris le front vers quinze heures, sans prévenir personne.",
        'Il pleuvait, donc évidemment je suis sorti.',
        "Séance ce matin, la fatigue est arrivée d'un coup après.",
        "Grosse journée au travail, je ne sais plus qui l'a commencée.",
        "On a retrouvé le carnet de l'an dernier.",
        "Je me suis réveillée avec une chanson en tête qui n'est pas la mienne.",
        "Rien de spécial aujourd'hui, et c'est déjà beaucoup.",
        'Après-midi au parc, sans téléphone.',
        "J'ai enfin appelé le cabinet.",
        'Nuit courte, mais bonne.',
        "Le chat a choisi mon côté du lit, je n'ai pas discuté.",
        "On a fini par tenir la liste des courses jusqu'au bout.",
        'Trois heures de train, deux livres commencés.',
        'Petite journée. On compte quand même les petites.',
        "J'ai raté le bus, j'ai marché, c'était mieux.",
        'On a redécoré le coin bureau, à quatre mains.',
        'Le pain a levé, contre toute attente.',
        'Je reprends les cours la semaine prochaine.',
        "Anniversaire du système aujourd'hui. Sept ans qu'on se parle.",
        "J'ai dit non à quelque chose, pour une fois.",
        'Pause déjeuner au soleil, les yeux fermés.',
    ];

    /** @var array<int, string> */
    public const POST_MIDDLES = [
        "Personne n'a eu besoin de prendre la relève, ce qui n'arrive pas si souvent.",
        "On s'est passé le front trois fois sans que ça casse le fil de la journée.",
        "J'ai écrit deux pages dans le carnet commun, les autres liront ce soir.",
        'Le calendrier partagé a servi à quelque chose pour une fois.',
        "Ça m'a pris deux heures de comprendre ce qui m'énervait.",
        "J'ai laissé un mot aux autres avant de partir, comme on avait convenu.",
        "La liste des choses à faire n'a pas bougé, tant pis pour elle.",
        "Le silence a fait plus de bien qu'une conversation.",
        "J'ai recommencé trois fois la même phrase avant de l'envoyer.",
        'On a discuté longtemps avant de décider, mais on a décidé ensemble.',
        "Le voisin a demandé notre prénom, j'ai donné le mien sans réfléchir.",
        "Je n'ai pas reconnu ma propre écriture sur le post-it du frigo.",
        "Quelqu'un avait déjà rangé la vaisselle, merci à qui que ce soit.",
        "La playlist s'est arrêtée toute seule et personne ne l'a relancée.",
        'On a mangé debout dans la cuisine, comme souvent.',
        "J'ai relu mes messages d'il y a un an, c'était étrange et doux.",
        'Le rendez-vous a été décalé, ça arrange tout le monde.',
        "Je me suis assise dehors jusqu'à ce qu'il fasse froid.",
        "Deux d'entre nous voulaient sortir, une voulait dormir. On a fait les deux.",
        "Le carnet dit que j'ai déjà vécu cette scène, je veux bien le croire.",
    ];

    /** @var array<int, string> */
    public const POST_CLOSERS = [
        "Demain sera ce qu'il sera.",
        'Bref, ça va.',
        'On verra bien qui se lève en premier.',
        'Je note ici pour ne pas oublier.',
        'Pas de conclusion, juste la journée.',
        "Si quelqu'un a un conseil, je prends.",
        'Merci à celui qui a pensé à sortir la poubelle.',
        "C'est déjà ça de pris.",
        'Je laisse le front à qui veut.',
        'Bonne nuit à qui lit ça tard.',
        'On réessaiera la semaine prochaine.',
        'Je vais me coucher tôt, promis.',
        '',
        '',
    ];

    /** @var array<int, string> */
    public const COMMENTS = [
        'Courage pour demain.',
        'Je connais bien ce genre de journée.',
        "Merci d'avoir écrit ça, ça aide de le lire.",
        'On a le même carnet, la même écriture illisible.',
        'Très joli, cette histoire de post-it.',
        'Tu as bien fait de dire non.',
        'Ça me rappelle nos premiers mois.',
        'Passe le bonjour aux autres.',
        'Le chat a raison, comme toujours.',
        'Je garde ça en tête pour notre prochaine séance.',
        "Bien joué pour l'appel, c'est le plus dur.",
        "J'espère que la nuit sera douce.",
        'On fait pareil ici, à trois.',
        'Merci pour la playlist, elle tourne chez nous.',
        'Content de te lire.',
        "Ça fait du bien de voir que ce n'est pas que nous.",
        "Tu diras au suivant qu'il a bien rangé.",
        'Repose-toi si tu peux.',
        "J'aurais pas su le dire aussi bien.",
        'On pense à vous.',
    ];

    /** @var array<int, string> */
    public const MESSAGES = [
        'Salut, tu es dispo cette semaine ?',
        'Je passe le front à Nori, elle répondra mieux que moi.',
        "Merci pour ton message d'hier, ça nous a fait du bien.",
        'On se voit jeudi ? Le café près de la gare marche pour nous.',
        "J'apporte le carnet, promis.",
        'Désolé pour le délai, la semaine a été longue.',
        "Tu peux me redire l'heure ? Je note tout deux fois.",
        "C'est noté dans le calendrier commun.",
        "On a lu ton post, on s'est reconnus dedans.",
        "Pas de souci si tu réponds tard, ici aussi c'est variable.",
        "Je préfère écrire plutôt qu'appeler, si ça te va.",
        'Bonne nouvelle : le rendez-vous est avancé.',
        "Je te laisse, quelqu'un d'autre arrive.",
        'Tu as réussi à dormir ?',
        'On a fini par retrouver le livre, il était sous le lit.',
        "Merci d'avoir prévenu, ça évite les malentendus.",
        'Je peux te rappeler demain matin ?',
        "C'est Sora qui écrit, au fait.",
        'Ça marche pour samedi.',
        'À très vite.',
    ];

    /** @var array<int, string> */
    public const SYSTEM_DESCRIPTIONS = [
        'Un compte, plusieurs personnes. Écrivez-nous, on répond à tour de rôle.',
        'Messagerie commune. Précisez à qui vous écrivez si ça compte pour vous.',
        "On partage cette boîte. Les réponses peuvent venir de l'un ou de l'autre.",
        'Boîte partagée. Nous sommes quatre à la relever.',
    ];
}

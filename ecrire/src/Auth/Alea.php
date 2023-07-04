<?php

namespace Spip\Auth;

enum Alea: string {
	case CURRENT = 'alea_ephemere';
	case PREVIOUS = 'alea_ephemere_ancien';
}

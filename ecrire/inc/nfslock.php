<?php
/* 
 *
 *
 * function: nfslock
 * original author: Chuck Cranor <chuck@maria.wustl.edu>
 * rewritten by: Alexis Rosen <alexis@panix.com>
 * rewritten by: Cedric Morin <cedric@yterium.com> for php&SPIP
 *
 * (Excerpts from Chuck's notes:
 *   this becomes complex, due to our dear friend, the NFS mounted mail spool.
 *   the netbsd code didn't do this properly, as far as I could tell.
 *
 *   - you can't trust exclusive creating opens over NFS, the protocol
 *   just doesn't support it.   so to do a lock you have to create
 *   a tmp file and then try and hard link it to your lock file.
 *   - to detect a stale lock file you have to see how old it is, but
 *   you can't use time(0) because that is the time on the local system
 *   and the file gets the times of the NFS server.  when is a lock
 *   file stale?  people seem to like 120 or 300 seconds.)
 *
 * NB: It is _critical_ that nfslock()ed files be unlocked by nfsunlock().
 * Simply unlinking the lock file is a good way to trash someone else's lock
 * file. All it takes is for the process doing the unlink to get hung for
 * a few minutes when it doesn't expect it. Meanwhile, its lock expires and
 * a second process forces the lock and creates its own. Then the first
 * process comes along and kills the second process' lock while it's still
 * valid.
 *
 * Security considerations:
 * If we're root, be very careful to see that the temp file we opened is
 * what we think it is. The problem is that we could lose a race with
 * someone who takes our tmp file and replaces it with, say, a hard
 * link to /etc/passwd. Then, if the first lock attempt fails, we'll
 * write a char to the file (see 4. below); this would truncate the
 * passwd file. So we make sure that the link count is 1. We don't really
 * care about any other screwing around since we don't write anything
 * sensitive to the lock file, nor do we change its owner or mode. If
 * someone beats us on a race and replaces our temp file with anything
 * else, it's no big deal- the file may get truncated, but there's no
 * possible security breach. ...Actually the possibility of the race
 * ever happening, given the random name of the file, is virtually nil.
 *
 * args: path = path to directory of lock file (/net/u/1/a/alexis/.mailspool)
 *       namelock = file name of lock file (alexis.lock)
 *	 max_age = age of lockfile, in seconds, after which the lock is stale.
 *		stale locks are always broken. Defaults to DEFAULT_LOCKTIME
 *		if zero. Panix mail locks go stale at 300 seconds, the default.
 *       notify = 1 if we should tell stdout that we're sleeping on a lock
 *
 * Returns the time that the lock was created on the other system. This is
 * important for nfsunlock(). If the lock already exists, returns NFSL_LOCKED.
 * If there is some other failure, return NFSL_SYSF. If NFSL_LOCKED is
 * returned, errno is also set to EEXIST. If we're root and the link count
 * on the tmp file is wrong, return NFSL_SECV.
 *
 * Mods of 7/13/95: Change a bit of code to re-stat the lockfile after
 * closing it. This is to work around a bug in SunOS that appears to to affect
 * some SunOS 4.1.3 machines (but not all). The bug is that close() updates
 * the stat st_ctime field for that file. So use lstat on fullpath instead
 * of fstat on tmpfd. This alteration applies to both nfslock and nfslock1.
 *
 * Mod of 5/4/95: Change printf's to fprintf(stderr... in nfslock and nfslock1.
 *
 * Mods of 4/29/95: Fix freeing memory before use if a stat fails. Remove
 * code that forbids running as root; instead, if root, check link count on
 * tmp file after opening it.
 *
 * Mods of 4/27/95: Return the create time instead of the lockfile's fd, which
 * is useless. Added new routines nfsunlock(), nfslock_test(), nfslock_renew().
 *
 * Mods of 1/8/95: Eliminate some security checks since this code never
 * runs as root. In particular, we completely eliminate the safeopen
 * routine. But add one check: if we _are_ root, fail immediately.
 *
 * Change arguments: take a path and a filename. Don't assume a global or
 * macro pointing to a mailspool.
 *
 * Add notify argument; if 1, tell user when we're waiting for a lock.
 *
 * Add max_age argument and DEFAULT_LOCKTIME.
 *
 * Change comments drastically.
 *
 */

include_spip('inc/acces');
define('_DEFAULT_LOCKTIME',60);
define('_NAME_LOCK','spip_nfs_lock');

function spip_nfslock($fichier,$max_age=0) {
	$tries = 0;
		
	if (!$max_age) $max_age = _DEFAULT_LOCKTIME;
	$lock_file = _DIR_TMP . _NAME_LOCK . "-" . substr(md5($fichier),0,8);

  
	/*
	 * 1. create a tmp file with a psuedo random file name. we also make
	 *    tpath which is a buffer to store the full pathname of the tmp file.
	 */

	$id = creer_uniqid();
	$tpath = _DIR_TMP."slock.$id";
	
	$tmpfd = @fopen($tpath, 'w'); // hum, le 'x' necessite php4,3,2 ...
	if (!$tmpfd) {	/* open failed */
		@fclose($tmpfd);
		spip_unlink($tpath);
		return false; //NFSL_SYSF
	}
  
	/*
	 * 2. make fullpath, a buffer for the full pathname of the lock file.
	 *    then start looping trying to lock it
	 */

	while ($tries < 10) {
		/*
		 * 3. link tmp file to lock file.  if it goes, we win and we clean
		 *    up and return the st_ctime of the lock file.
		 */

		if (link($tpath, $lock_file) == 1) {
			spip_unlink($tpath); /* got it! */
			@fclose($tmpfd);
			if (($our_tmp = lstat($lock_file))==false) {	/* stat failed... shouldn't happen */
				spip_unlink($lock_file);
				return false; // (NFSL_SYSF);
			}
			return($our_tmp['ctime']);
		}

		/*
		 * 4. the lock failed.  check for a stale lock file, being mindful
		 *    of NFS and the fact the time is set from the NFS server.  we
		 *    do a write on the tmp file to update its time to the server's
		 *    idea of "now."
		 */

		$old_stat = lstat($lock_file);
		if (@fputs($tmpfd, "zz", 2)!=2 || !$our_tmp=fstat($tmpfd))
			break; /* something bogus is going on */


		if ($old_stat!=false && (($old_stat['ctime'] + $max_age) < $our_tmp['ctime'])) {
			spip_unlink($lock_file); /* break the stale lock */
			$tries++;
			/* It is CRITICAL that we sleep after breaking
			 * the lock. Otherwise, we could race with
			 * another process and unlink it's newly-
			 * created file.
			 */
			sleep(1+rand(0,4));
			continue;
		}

		/*
		 * 5. try again
		 */

    $tries++;
		sleep(1+rand(0,4));
  }

	/*
	 * 6. give up, failure.
	 */

	spip_unlink($tpath);
	@fclose($tmpfd);
	return false; //(NFSL_LOCKED);
}

/*
 * function: nfsunlock
 * author: Alexis Rosen <alexis@panix.com>
 *
 * Unlock an nfslock()ed file.
 *
 * This can get tricky because the lock may have expired (perhaps even
 * during a process that should be "atomic"). We have to make sure we don't
 * unlock some other process' lock, and return a panic code if we think our
 * lock file has been broken illegally. What's done in reaction to that panic
 * (of anything) is up to the caller. See the comments on nfslock()!
 *
 * args: path = path to directory of lock file (/net/u/1/a/alexis/.mailspool)
 *       namelock = file name of lock file (alexis.lock)
 *       max_age = age of lockfile, in seconds, after which the lock is stale.
 *		stale locks are always broken. Defaults to DEFAULT_LOCKTIME
 *		if zero. Panix mail locks go stale at 300 seconds, the default.
 *	 birth = time the lock was created (as returned by nfslock()).
 *
 * Returns NFSL_OK if successful, NFSL_LOST if the lock has been lost
 * legitimately (because more than max_age has passed since the lock was
 * created), and NFSL_STOLEN if it's been tampered with illegally (i.e.
 * while this program is within the expiry period). Returns NFSL_SYSF if
 * another system failure prevents it from even trying to unlock the file.
 *
 * Note that for many programs, a return code of NFSL_LOST or NFSL_STOLEN is
 * equally disastrous; a NFSL_STOLEN means that some other program may have
 * trashed your file, but a NFSL_LOST may mean that _you_ have trashed someone
 * else's file (if in fact you wrote the file that you locked after you lost
 * the lock) or that you read inconsistent information.
 *
 * In practice, a return code of NFSL_LOST or NFSL_STOLEN will virtually never
 * happen unless someone is violating the locking protocol.
 *
 */

function spip_nfsunlock($fichier, $birth, $max_age=0, $test = false) {
	$id = creer_uniqid();
	if (!$max_age) $max_age = _DEFAULT_LOCKTIME;

	/*
	 * 1. Build a temp file and stat that to get an idea of what the server
	 *    thinks the current time is (our_tmp.st_ctime)..
	 */

	$tpath = _DIR_TMP."stime.$id";
  $tmpfd = @fopen($tpath,'w');
  if ((!$tmpfd)
    OR (@fputs($tmpfd, "zz", 2) != 2)
		OR !($our_tmp = fstat($tmpfd))) {
		/* The open failed, or we can't write the file, or we can't stat it */
		@fclose($tmpfd);
		spip_unlink($tpath);
		return false; //(NFSL_SYSF);
	}

	@fclose($tmpfd);		/* We don't need this once we have our_tmp.st_ctime. */
	spip_unlink($tpath);

	/*
	 * 2. make fullpath, a buffer for the full pathname of the lock file
	 */

	$lock_file = _DIR_TMP . _NAME_LOCK . "-" . substr(md5($fichier),0,8);

	/*
	 * 3. If the ctime hasn't been modified, unlink the file and return. If the
	 *    lock has expired, sleep the usual random interval before returning.
	 *    If we didn't sleep, there could be a race if the caller immediately
	 *    tries to relock the file.
	 */

  if ( ($old_stat=lstat($lock_file))	/* stat succeeds so file is there */
	  && ($old_stat['ctime'] == $birth)) {	/* hasn't been modified since birth */
	  if (!$test)
    	spip_unlink($lock_file);			/* so the lock is ours to remove */
    if ($our_tmp['ctime'] >= $birth + $max_age){	/* the lock has expired */
    	if (!$test) return false; //(NFSL_LOST);
      sleep(1+(random(0,4)));		/* so sleep a bit */
    }
		return true;//(NFSL_OK);			/* success */
  }

	/*
	 * 4. Either ctime has been modified, or the entire lock file is missing.
	 *    If the lock should still be ours, based on the ctime of the temp
	 *    file, return with NFSL_STOLEN. If not, then our lock is expired and
	 *    someone else has grabbed the file, so return NFSL_LOST.
	 */

	if ($our_tmp['ctime'] < $birth + $max_age)	/* lock was stolen */
		return false; //(NFSL_STOLEN);

	return false; //(NFSL_LOST);	/* The lock must have expired first. */
}


/*
 * function: nfslock_test
 * author: Alexis Rosen <alexis@panix.com>
 *
 * Test a lock to see if it's still valid.
 *
 * See the comments in nfsunlock() about lost and stolen locks.
 *
 * Args, return codes, and behavior are identical to nfsunlock except
 * that nfslock_test doesn't remove the lock. NFSL_OK means the lock is
 * good, NFLS_LOST and NFSL_STOLEN means it's bad, and NFSL_SYSF means
 * we couldn't tell due to system failure.
 *
 * The source for this routine is almost identical to nfsunlock(), but it's
 * coded separately to make things as clear as possible.
 */

function spip_nfslock_test($fichier, $birth, $max_age=0) {
	return spip_nfsunlock($fichier, $birth, $max_age, true);
}

?>
<?php

namespace Drupal\visitors\Commands;

use Drupal\Core\State\StateInterface;
use Drupal\visitors\VisitorsRebuildIpAddressInterface;
use Drupal\visitors\VisitorsRebuildRouteInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines a Drush command.
 */
class RebuildCommands extends DrushCommands {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The visitors rebuild route service.
   *
   * @var \Drupal\visitors\VisitorsRebuildRouteInterface
   */
  protected $route;

  /**
   * The visitors rebuild ip address service.
   *
   * @var \Drupal\visitors\VisitorsRebuildIpAddressInterface
   */
  protected $address;

  /**
   * Drush commands for rebuilding logs.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\visitors\VisitorsRebuildRouteInterface $route
   *   The visitors rebuild route service.
   * @param \Drupal\visitors\VisitorsRebuildIpAddressInterface $ip_address
   *   The visitors rebuild ip address service.
   */
  public function __construct(
    StateInterface $state,
    VisitorsRebuildRouteInterface $route,
    VisitorsRebuildIpAddressInterface $ip_address
    ) {
    parent::__construct();

    $this->state = $state;
    $this->route = $route;
    $this->address = $ip_address;
  }

  /**
   * Regenerates routes from path.
   *
   * @command visitors:rebuild:route
   * @aliases visitors-rebuild-route
   *
   * @usage drush visitors:rebuild:route
   *  Generates routes from the visitors_path.
   */
  public function routes() {

    $records = $this->route->getPaths();
    $total = count($records);

    // Get the Symfony Console output interface.
    $output = $this->output();
    $output->writeLn("There are $total paths to process.");
    $progressBar = new ProgressBar($output, $total);
    $progressBar->setFormat('debug');
    $progressBar->start();

    do {
      $progressBar->advance();
      $record = array_pop($records);
      if (empty($record)) {
        continue;
      }

      $this->route->rebuild($record->visitors_path);

    } while (count($records));

    // Finish the progress bar.
    $progressBar->finish();
    // Add a new line after the progress bar.
    $output->writeln('');

    $this->state->delete('visitors.rebuild.route');

    // Output a completion message.
    $output->writeln('Task completed!');
  }

  /**
   * Converts IP Address to support IPv6.
   *
   * @command visitors:rebuild:ip-address
   * @aliases visitors-rebuild-ip-address
   *
   * @usage drush visitors:rebuild:ip-address
   *  Converts integers IP addresses to strings.
   */
  public function addresses() {

    $records = $this->address->getIpAddresses();
    $total = count($records);

    // Get the Symfony Console output interface.
    $output = $this->output();
    $output->writeLn("There are $total ip addresses to process.");
    $progressBar = new ProgressBar($output, $total);
    $progressBar->setFormat('debug');
    $progressBar->start();

    do {
      $progressBar->advance();
      $record = array_pop($records);
      if (empty($record)) {
        continue;
      }

      $this->address->rebuild($record->visitors_ip);

    } while (count($records));

    // Finish the progress bar.
    $progressBar->finish();
    // Add a new line after the progress bar.
    $output->writeln('');

    $this->state->delete('visitors.rebuild.ip_address');

    // Output a completion message.
    $output->writeln('Task completed!');
  }

}
